<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoicePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoicePartialPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $nonAdminUser;
    protected Customer $customer;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'Super Admin',
            'department' => 'Finance',
        ]);

        $this->nonAdminUser = User::factory()->create([
            'role' => 'Manager',
            'department' => 'Creative',
        ]);

        $this->customer = Customer::create([
            'name' => 'Acme Corporation',
            'email' => 'billing@acme.com',
            'phone' => '1234567890',
        ]);

        $this->invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2026-001',
            'date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'total_amount' => 100000.00,
            'status' => 'unpaid',
            'is_proforma' => false,
            'currency' => 'LKR',
        ]);
    }

    public function test_super_admin_can_fetch_invoice_payments()
    {
        InvoicePayment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => 25000.00,
            'payment_date' => '2026-10-10',
            'payment_method' => 'Bank Transfer',
            'note' => 'First installment',
            'recorded_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('invoices.payments.index', $this->invoice));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.total_amount', 100000)
            ->assertJsonPath('invoice.total_paid', 25000)
            ->assertJsonPath('invoice.due_amount', 75000)
            ->assertJsonCount(1, 'payments');
    }

    public function test_super_admin_can_record_partial_payment_and_status_becomes_partial()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('invoices.payments.store', $this->invoice), [
                'amount' => 40000.00,
                'payment_date' => '2026-10-14',
                'payment_method' => 'Cheque',
                'note' => 'Cheque #12345',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.status', 'partial')
            ->assertJsonPath('invoice.total_paid', 40000)
            ->assertJsonPath('invoice.due_amount', 60000);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
        $this->assertEquals(40000.00, $this->invoice->total_paid_amount);
        $this->assertEquals(60000.00, $this->invoice->due_amount);
        $this->assertTrue($this->invoice->isPartiallyPaid());
        $this->assertFalse($this->invoice->isFullyPaidThroughPartial());
    }

    public function test_recording_full_balance_marks_invoice_as_paid()
    {
        // First payment 50k
        $this->actingAs($this->superAdmin)
            ->postJson(route('invoices.payments.store', $this->invoice), [
                'amount' => 50000.00,
                'payment_date' => '2026-10-10',
            ]);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);

        // Second payment 50k (completes 100k)
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('invoices.payments.store', $this->invoice), [
                'amount' => 50000.00,
                'payment_date' => '2026-10-14',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.status', 'paid')
            ->assertJsonPath('invoice.due_amount', 0);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals(100000.00, $this->invoice->total_paid_amount);
        $this->assertEquals(0.00, $this->invoice->due_amount);
        $this->assertTrue($this->invoice->isFullyPaidThroughPartial());
    }

    public function test_deleting_partial_payment_recalculates_status_and_balance()
    {
        $payment1 = InvoicePayment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => 60000.00,
            'payment_date' => '2026-10-10',
            'recorded_by' => $this->superAdmin->id,
        ]);

        $payment2 = InvoicePayment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => 40000.00,
            'payment_date' => '2026-10-14',
            'recorded_by' => $this->superAdmin->id,
        ]);

        $this->invoice->update(['status' => 'paid']);

        // Delete payment 2
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson(route('invoices.payments.destroy', [$this->invoice, $payment2]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.status', 'partial')
            ->assertJsonPath('invoice.due_amount', 40000);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
        $this->assertEquals(60000.00, $this->invoice->total_paid_amount);
        $this->assertEquals(40000.00, $this->invoice->due_amount);

        // Delete payment 1
        $this->actingAs($this->superAdmin)
            ->deleteJson(route('invoices.payments.destroy', [$this->invoice, $payment1]));

        $this->invoice->refresh();
        $this->assertEquals('unpaid', $this->invoice->status);
        $this->assertEquals(0.00, $this->invoice->total_paid_amount);
        $this->assertEquals(100000.00, $this->invoice->due_amount);
    }

    public function test_non_super_admin_cannot_record_or_delete_payment()
    {
        $response = $this->actingAs($this->nonAdminUser)
            ->postJson(route('invoices.payments.store', $this->invoice), [
                'amount' => 10000.00,
                'payment_date' => '2026-10-14',
            ]);

        $response->assertForbidden();

        $payment = InvoicePayment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => 10000.00,
            'payment_date' => '2026-10-14',
        ]);

        $deleteResponse = $this->actingAs($this->nonAdminUser)
            ->deleteJson(route('invoices.payments.destroy', [$this->invoice, $payment]));

        $deleteResponse->assertForbidden();
    }

    public function test_status_update_route_accepts_partial()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('invoices.updateStatus', $this->invoice), [
                'status' => 'partial',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'partial');

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
    }

    public function test_invoices_index_renders_payment_status_column_dropdown_and_modal()
    {
        // 1. Partial invoice
        $partialInvoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-PARTIAL-001',
            'date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'total_amount' => 50000.00,
            'status' => 'partial',
            'is_proforma' => false,
            'currency' => 'LKR',
        ]);
        InvoicePayment::create([
            'invoice_id' => $partialInvoice->id,
            'amount' => 20000.00,
            'payment_date' => '2026-10-10',
            'recorded_by' => $this->superAdmin->id,
        ]);

        // 2. Fully paid through partial invoice
        $fullyPaidPartial = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-FULLPART-002',
            'date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'total_amount' => 30000.00,
            'status' => 'paid',
            'is_proforma' => false,
            'currency' => 'LKR',
        ]);
        InvoicePayment::create([
            'invoice_id' => $fullyPaidPartial->id,
            'amount' => 30000.00,
            'payment_date' => '2026-10-10',
            'recorded_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('invoices.index'));

        $response->assertOk()
            ->assertSee('Payment Status')
            ->assertSee('Partial Payment')
            ->assertSee('Due: LKR 30,000.00')
            ->assertDontSee('Paid: LKR 20,000.00')
            ->assertSee('fa-info-circle')
            ->assertSee('Payment Details')
            ->assertSee('Record Partial Payment');
    }

    public function test_update_status_blocks_unpaid_or_paid_when_partial_payments_exist()
    {
        InvoicePayment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => 10000.00,
            'payment_date' => '2026-10-10',
            'recorded_by' => $this->superAdmin->id,
        ]);

        $this->invoice->update(['status' => 'partial']);

        // Cannot mark as unpaid
        $responseUnpaid = $this->actingAs($this->superAdmin)
            ->postJson(route('invoices.updateStatus', $this->invoice), ['status' => 'unpaid']);
        $responseUnpaid->assertStatus(422)
            ->assertJsonPath('success', false);

        // Cannot mark as paid while due > 0
        $responsePaid = $this->actingAs($this->superAdmin)
            ->postJson(route('invoices.updateStatus', $this->invoice), ['status' => 'paid']);
        $responsePaid->assertStatus(422)
            ->assertJsonPath('success', false);

        // Overdue is allowed
        $responseOverdue = $this->actingAs($this->superAdmin)
            ->postJson(route('invoices.updateStatus', $this->invoice), ['status' => 'overdue']);
        $responseOverdue->assertOk()
            ->assertJsonPath('success', true);
    }
}
