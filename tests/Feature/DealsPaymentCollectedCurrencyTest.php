<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Deal;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoicePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DealsPaymentCollectedCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Super Admin',
            'department' => 'Finance',
        ]);

        $this->customer = Customer::create([
            'name' => 'Global Corp',
            'email' => 'finance@global.com',
            'phone' => '1234567890',
        ]);
    }

    public function test_payment_collected_sums_only_lkr_and_separates_other_currencies()
    {
        // 1. Deal in LKR with paid invoice (50,000 LKR)
        $dealLkr = Deal::create([
            'title' => 'LKR Project',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'stage' => 'Closed Won',
            'currency' => 'LKR',
            'revenue' => 100000,
            'contribution' => 80000,
        ]);
        $estimate1 = Estimate::create([
            'deal_id' => $dealLkr->id,
            'customer_id' => $this->customer->id,
            'reference_number' => 'EST-001',
            'date' => '2026-10-01',
            'version' => 1,
            'status' => 'approved',
            'currency' => 'LKR',
            'sub_total' => 100000,
            'total_amount' => 100000,
        ]);
        $invoicePaidLkr = Invoice::create([
            'quotation_id' => $estimate1->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-LKR-1',
            'date' => '2026-10-01',
            'due_date' => '2026-10-31',
            'total_amount' => 50000.00,
            'status' => 'paid',
            'is_proforma' => false,
            'currency' => 'LKR',
        ]);

        // 2. Deal in LKR with partial payments (30,000 LKR collected of 80,000)
        $dealPartial = Deal::create([
            'title' => 'Partial LKR Project',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'stage' => 'Presentation',
            'currency' => 'LKR',
            'revenue' => 80000,
            'contribution' => 60000,
        ]);
        $estimate2 = Estimate::create([
            'deal_id' => $dealPartial->id,
            'customer_id' => $this->customer->id,
            'reference_number' => 'EST-002',
            'date' => '2026-10-01',
            'version' => 1,
            'status' => 'approved',
            'currency' => 'LKR',
            'sub_total' => 80000,
            'total_amount' => 80000,
        ]);
        $invoicePartial = Invoice::create([
            'quotation_id' => $estimate2->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-LKR-2',
            'date' => '2026-10-02',
            'due_date' => '2026-10-31',
            'total_amount' => 80000.00,
            'status' => 'partial',
            'is_proforma' => false,
            'currency' => 'LKR',
        ]);
        InvoicePayment::create([
            'invoice_id' => $invoicePartial->id,
            'amount' => 20000.00,
            'payment_date' => '2026-10-05',
            'payment_method' => 'Bank Transfer',
        ]);
        InvoicePayment::create([
            'invoice_id' => $invoicePartial->id,
            'amount' => 10000.00,
            'payment_date' => '2026-10-10',
            'payment_method' => 'Cash',
        ]);

        // 3. Deal in USD with paid invoice (500 USD)
        $dealUsd = Deal::create([
            'title' => 'USD International Project',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'stage' => 'Closed Won',
            'currency' => 'USD',
            'revenue' => 1000,
            'contribution' => 900,
        ]);
        $estimate3 = Estimate::create([
            'deal_id' => $dealUsd->id,
            'customer_id' => $this->customer->id,
            'reference_number' => 'EST-003',
            'date' => '2026-10-01',
            'version' => 1,
            'status' => 'approved',
            'currency' => 'USD',
            'sub_total' => 1000,
            'total_amount' => 1000,
        ]);
        $invoicePaidUsd = Invoice::create([
            'quotation_id' => $estimate3->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-USD-1',
            'date' => '2026-10-03',
            'due_date' => '2026-10-31',
            'total_amount' => 500.00,
            'status' => 'paid',
            'is_proforma' => false,
            'currency' => 'USD',
        ]);

        // Act: visit Deals index
        $response = $this->actingAs($this->admin)->get(route('deals.index'));

        // Assert
        $response->assertStatus(200);

        // Expected LKR collected: 50,000 + 30,000 = 80,000.00 (NOT including 500 USD!)
        $this->assertEquals(80000.00, $response->viewData('paymentCollected'));

        // Expected other currencies: USD => 500.00
        $otherCurrencies = $response->viewData('otherCurrenciesPaymentCollected');
        $this->assertIsArray($otherCurrencies);
        $this->assertArrayHasKey('USD', $otherCurrencies);
        $this->assertEquals(500.00, $otherCurrencies['USD']);
        $this->assertArrayNotHasKey('LKR', $otherCurrencies);

        // View assertions
        $response->assertSee('Payment Collected');
        $response->assertSee('80,000.00');
        $response->assertSee('Other Currencies');
        $response->assertSee('500.00');
    }
}
