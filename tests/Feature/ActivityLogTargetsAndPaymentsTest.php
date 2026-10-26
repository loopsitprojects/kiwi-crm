<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Activity;
use App\Models\TargetPeriod;
use App\Models\InvoicePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTargetsAndPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'Super Admin',
            'department' => 'Creative',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Client Ltd',
            'email' => 'client@example.com',
            'phone' => '0771234567',
        ]);

        $this->invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-TEST-2026',
            'date' => '2026-10-01',
            'due_date' => '2026-10-31',
            'total_amount' => 50000.00,
            'status' => 'unpaid',
            'currency' => 'LKR',
        ]);
    }

    public function test_logging_when_adding_target_period()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 11,
            'prefill' => 'default',
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Added target period for November 2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for adding target period was not created.');
        $this->assertEquals($this->admin->id, $activity->user_id);
        $this->assertEquals(2026, $activity->properties['year']);
        $this->assertEquals(11, $activity->properties['month']);
    }

    public function test_logging_when_adding_all_target_periods()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2027,
            'month' => 'all',
            'prefill' => 'zero',
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Added target periods for all 12 months of 2027%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for adding all 12 target periods was not created.');
        $this->assertEquals($this->admin->id, $activity->user_id);
        $this->assertEquals('zero', $activity->properties['prefill']);
    }

    public function test_logging_when_updating_department_targets()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.updateDepartmentTargets'), [
            'year' => 2026,
            'month' => 10,
            'targets' => [
                'Creative' => 150000,
                'Digital' => 200000,
            ],
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Updated department targets for October 2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for updating department targets was not created.');
        $this->assertEquals($this->admin->id, $activity->user_id);
        $this->assertEquals(150000, $activity->properties['targets']['Creative']);
    }

    public function test_logging_when_applying_department_targets_to_all_months()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.updateDepartmentTargets'), [
            'year' => 2026,
            'month' => 10,
            'apply_to_all_months' => '1',
            'targets' => [
                'Creative' => 180000,
            ],
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Updated department targets for all 12 months of 2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for applying department targets to all months was not created.');
        $this->assertTrue($activity->properties['apply_to_all_months']);
    }

    public function test_logging_when_updating_user_targets()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.updateUserTargets'), [
            'year' => 2026,
            'month' => 10,
            'targets' => [
                $this->admin->id => 75000,
            ],
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Updated user targets for October 2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for updating user targets was not created.');
        $this->assertEquals(75000, $activity->properties['targets'][$this->admin->id]);
    }

    public function test_logging_when_deleting_single_target()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.deleteSingleTarget'), [
            'type' => 'department',
            'department' => 'Creative',
            'year' => 2026,
            'month' => 10,
            'scope' => 'month',
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Cleared target for Creative (Department) for October 2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for deleting single target was not created.');
        $this->assertEquals('month', $activity->properties['scope']);
    }

    public function test_logging_when_deleting_target_period_values()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.deleteTargetPeriod'), [
            'year' => 2026,
            'month' => 10,
            'scope' => 'month',
            'type' => 'department',
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Cleared Department targets for October 2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for clearing target period was not created.');
        $this->assertEquals('department', $activity->properties['type']);
    }

    public function test_logging_when_destroying_target_period()
    {
        TargetPeriod::firstOrCreate(['year' => 2026, 'month' => 11], ['name' => 'November 2026']);

        $response = $this->actingAs($this->admin)->delete(route('settings.destroyTargetPeriod'), [
            'year' => 2026,
            'month' => 11,
        ]);

        $response->assertRedirect();

        $activity = Activity::where('description', 'like', '%Deleted target period and targets for November 2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for destroying target period was not created.');
    }

    public function test_logging_when_recording_partial_invoice_payment()
    {
        $response = $this->actingAs($this->admin)->postJson(route('invoices.payments.store', $this->invoice), [
            'amount' => 20000.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'Bank Transfer',
            'note' => 'Part 1',
        ]);

        $response->assertOk();

        $activity = Activity::where('description', 'like', '%Recorded partial payment of LKR 20,000.00 via Bank Transfer for invoice: INV-TEST-2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for partial payment was not created.');
        $this->assertEquals(Invoice::class, $activity->subject_type);
        $this->assertEquals($this->invoice->id, $activity->subject_id);
        $this->assertEquals(20000.00, $activity->properties['amount']);
        $this->assertEquals('Bank Transfer', $activity->properties['payment_method']);
        $this->assertEquals(30000.00, $activity->properties['due_amount']);
    }

    public function test_logging_when_recording_full_invoice_payment()
    {
        $response = $this->actingAs($this->admin)->postJson(route('invoices.payments.store', $this->invoice), [
            'amount' => 50000.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'Cheque',
            'note' => 'Full Payment',
        ]);

        $response->assertOk();

        $activity = Activity::where('description', 'like', '%Recorded full payment of LKR 50,000.00 via Cheque for invoice: INV-TEST-2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for full payment was not created.');
        $this->assertEquals('paid', $activity->properties['invoice_status']);
        $this->assertEquals(0.00, $activity->properties['due_amount']);
    }

    public function test_logging_when_deleting_invoice_payment()
    {
        $payment = InvoicePayment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => 15000.00,
            'payment_date' => '2026-10-12',
            'payment_method' => 'Cash',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson(route('invoices.payments.destroy', [
            'invoice' => $this->invoice->id,
            'payment' => $payment->id,
        ]));

        $response->assertOk();

        $activity = Activity::where('description', 'like', '%Deleted payment of LKR 15,000.00 (Cash) from invoice: INV-TEST-2026%')->latest()->first();
        $this->assertNotNull($activity, 'Activity log for deleting invoice payment was not created.');
        $this->assertEquals(15000.00, $activity->properties['deleted_amount']);
        $this->assertEquals('Cash', $activity->properties['payment_method']);
    }

    public function test_activities_index_displays_target_and_payment_activities()
    {
        // Perform a target action
        $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 12,
            'prefill' => 'default',
        ]);

        // Perform a payment action
        $this->actingAs($this->admin)->postJson(route('invoices.payments.store', $this->invoice), [
            'amount' => 10000.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'Online',
        ]);

        $response = $this->actingAs($this->admin)->get(route('activities.index'));
        $response->assertOk();
        $response->assertSee('Added target period for December 2026');
        $response->assertSee('Recorded partial payment of LKR 10,000.00 via Online for invoice: INV-TEST-2026');
    }
}
