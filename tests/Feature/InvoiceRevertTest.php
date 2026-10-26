<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\TempInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceRevertTest extends TestCase
{
    use RefreshDatabase;

    private User $itAdmin;
    private User $financeAdmin;
    private User $superAdmin;
    private User $management;
    private User $staff;
    private Customer $customer;
    private Estimate $estimate;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itAdmin = User::factory()->create([
            'name' => 'IT Admin User',
            'email' => 'itadmin@loops.com',
            'role' => 'IT Admin',
        ]);

        $this->financeAdmin = User::factory()->create([
            'name' => 'Finance Admin User',
            'email' => 'finance@loops.com',
            'role' => 'Finance Admin',
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'super@loops.com',
            'role' => 'Super Admin',
        ]);

        $this->management = User::factory()->create([
            'name' => 'Management User',
            'email' => 'mgmt@loops.com',
            'role' => 'Management',
        ]);

        $this->staff = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@loops.com',
            'role' => 'Staff',
        ]);

        $this->customer = Customer::create([
            'name' => 'Acme Corporation',
            'email' => 'acme@example.com',
            'brand' => 'Acme',
        ]);

        $deal = Deal::create([
            'title' => 'Acme Campaign',
            'customer_id' => $this->customer->id,
            'user_id' => $this->itAdmin->id,
            'customer_name' => $this->customer->name,
            'stage' => 'Closed Won',
            'revenue' => 100000,
        ]);

        $this->estimate = Estimate::create([
            'customer_id' => $this->customer->id,
            'deal_id' => $deal->id,
            'reference_number' => 'EST/2026/0001',
            'brand_name' => 'Acme',
            'currency' => 'LKR',
            'total_amount' => 100000,
            'status' => 'invoiced',
            'date' => '2026-10-13',
        ]);

        $this->estimate->items()->create([
            'description' => 'Creative Campaign Asset',
            'type' => 'item',
            'quantity' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
            'sscl_amount' => 0,
            'vat_amount' => 0,
            'total_with_vat' => 100000,
            'department' => 'Creative',
            'revenue_category' => 'Main',
        ]);

        $this->invoice = Invoice::create([
            'quotation_id' => $this->estimate->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => '26OCT_LDSL_00001',
            'date' => '2026-10-13',
            'due_date' => '2026-11-13',
            'total_amount' => 100000,
            'status' => 'unpaid',
            'currency' => 'LKR',
        ]);

        $this->invoice->items()->create([
            'description' => 'Creative Campaign Asset',
            'type' => 'item',
            'quantity' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
            'sscl_amount' => 0,
            'vat_amount' => 0,
            'total_with_vat' => 100000,
            'department' => 'Creative',
            'revenue_category' => 'Main',
        ]);
    }

    public function test_it_admin_can_revert_invoice_to_ready_to_invoice_state(): void
    {
        $response = $this->actingAs($this->itAdmin)
            ->post(route('invoices.revert', $this->invoice), [
                'target_state' => 'ready_to_invoice',
            ]);

        $response->assertRedirect(route('invoices.ready'));
        $response->assertSessionHas('success');

        // Invoice record should be deleted
        $this->assertDatabaseMissing('invoices', [
            'id' => $this->invoice->id,
        ]);

        // Invoice items should be deleted
        $this->assertDatabaseMissing('invoice_items', [
            'invoice_id' => $this->invoice->id,
        ]);

        // Estimate should be in ready_to_invoice state
        $this->estimate->refresh();
        $this->assertEquals('ready_to_invoice', $this->estimate->status);

        // TempInvoice should be created
        $tempInvoice = TempInvoice::where('quotation_id', $this->estimate->id)->first();
        $this->assertNotNull($tempInvoice);
        $this->assertStringStartsWith('TEMP_', $tempInvoice->temp_invoice_number);
        $this->assertCount(1, $tempInvoice->items);
        $this->assertEquals('Creative Campaign Asset', $tempInvoice->items->first()->description);

        // Activity log recorded
        $this->assertDatabaseHas('activities', [
            'description' => "Reverted invoice 26OCT_LDSL_00001 to Ready to Invoice state",
        ]);
    }

    public function test_it_admin_can_revert_invoice_to_estimate_state(): void
    {
        // First create a temp invoice to ensure it gets cleaned up
        TempInvoice::create([
            'quotation_id' => $this->estimate->id,
            'customer_id' => $this->customer->id,
            'temp_invoice_number' => 'TEMP_00001',
            'date' => '2026-10-13',
            'due_date' => '2026-11-13',
            'total_amount' => 100000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->itAdmin)
            ->post(route('invoices.revert', $this->invoice), [
                'target_state' => 'draft',
            ]);

        $response->assertRedirect(route('estimates.index'));
        $response->assertSessionHas('success');

        // Invoice record should be deleted
        $this->assertDatabaseMissing('invoices', [
            'id' => $this->invoice->id,
        ]);

        // Estimate should be in draft state
        $this->estimate->refresh();
        $this->assertEquals('draft', $this->estimate->status);

        // TempInvoice should be deleted
        $this->assertDatabaseMissing('temp_invoices', [
            'quotation_id' => $this->estimate->id,
        ]);

        // Activity log recorded
        $this->assertDatabaseHas('activities', [
            'description' => "Reverted invoice 26OCT_LDSL_00001 to Estimate (Draft) state",
        ]);
    }

    public function test_it_admin_can_revert_via_ajax_request(): void
    {
        $response = $this->actingAs($this->itAdmin)
            ->postJson(route('invoices.revert', $this->invoice), [
                'target_state' => 'ready_to_invoice',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect' => route('invoices.ready'),
        ]);

        $this->assertDatabaseMissing('invoices', ['id' => $this->invoice->id]);
    }

    public function test_non_it_admin_roles_are_forbidden_from_reverting(): void
    {
        // Finance Admin
        $resFinance = $this->actingAs($this->financeAdmin)
            ->post(route('invoices.revert', $this->invoice), [
                'target_state' => 'ready_to_invoice',
            ]);
        $resFinance->assertForbidden();

        // Super Admin
        $resSuper = $this->actingAs($this->superAdmin)
            ->post(route('invoices.revert', $this->invoice), [
                'target_state' => 'ready_to_invoice',
            ]);
        $resSuper->assertForbidden();

        // Management
        $resMgmt = $this->actingAs($this->management)
            ->post(route('invoices.revert', $this->invoice), [
                'target_state' => 'ready_to_invoice',
            ]);
        $resMgmt->assertForbidden();

        // Staff
        $resStaff = $this->actingAs($this->staff)
            ->post(route('invoices.revert', $this->invoice), [
                'target_state' => 'ready_to_invoice',
            ]);
        $this->assertTrue(in_array($resStaff->status(), [302, 403]));

        // Invoice should still exist untouched
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
        ]);
    }

    public function test_invalid_target_state_fails_validation(): void
    {
        $response = $this->actingAs($this->itAdmin)
            ->post(route('invoices.revert', $this->invoice), [
                'target_state' => 'invalid_state',
            ]);

        $response->assertSessionHasErrors('target_state');
        $this->assertDatabaseHas('invoices', ['id' => $this->invoice->id]);
    }

    public function test_revert_estimate_invoice_when_estimate_has_no_invoice(): void
    {
        // Standalone estimate in invoiced state without an invoice record
        $estimateNoInv = Estimate::create([
            'customer_id' => $this->customer->id,
            'reference_number' => 'EST/2026/0002',
            'brand_name' => 'Acme',
            'currency' => 'LKR',
            'total_amount' => 50000,
            'status' => 'invoiced',
            'date' => '2026-10-13',
        ]);

        $response = $this->actingAs($this->itAdmin)
            ->post(route('estimates.revertInvoice', $estimateNoInv), [
                'target_state' => 'ready_to_invoice',
            ]);

        $response->assertRedirect(route('invoices.ready'));
        $estimateNoInv->refresh();
        $this->assertEquals('ready_to_invoice', $estimateNoInv->status);
        $this->assertDatabaseHas('temp_invoices', ['quotation_id' => $estimateNoInv->id]);
    }

    public function test_ui_visibility_only_for_it_admin(): void
    {
        // Invoices index
        $resItIndex = $this->actingAs($this->itAdmin)->get(route('invoices.index'));
        $resItIndex->assertOk();
        $resItIndex->assertSee('openRevertInvoiceModal');

        $resFinanceIndex = $this->actingAs($this->financeAdmin)->get(route('invoices.index'));
        $resFinanceIndex->assertOk();
        $resFinanceIndex->assertDontSee('openRevertInvoiceModal');

        // Invoice show
        $resItShow = $this->actingAs($this->itAdmin)->get(route('invoices.show', $this->invoice));
        $resItShow->assertOk();
        $resItShow->assertSee('Revert Invoice');

        $resFinanceShow = $this->actingAs($this->financeAdmin)->get(route('invoices.show', $this->invoice));
        $resFinanceShow->assertOk();
        $resFinanceShow->assertDontSee('Revert Invoice');

        // Invoiced estimates
        $resItInvoiced = $this->actingAs($this->itAdmin)->get(route('invoices.invoiced'));
        $resItInvoiced->assertOk();
        $resItInvoiced->assertSee('openRevertInvoiceModal');

        $resFinanceInvoiced = $this->actingAs($this->financeAdmin)->get(route('invoices.invoiced'));
        $resFinanceInvoiced->assertOk();
        $resFinanceInvoiced->assertDontSee('openRevertInvoiceModal');
    }
}
