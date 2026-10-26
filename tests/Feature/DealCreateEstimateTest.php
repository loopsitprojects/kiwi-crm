<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealCreateEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_estimate_from_deal_automatically_populates_customer_and_client_details(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
            'department' => 'Creative',
        ]);
        $this->actingAs($user);

        $customer = Customer::create([
            'name' => 'Acme Corporation Ltd',
            'brand' => 'Acme Brand',
            'email' => 'client@acme.com',
            'phone' => '0112345678',
            'billing_address' => "No 123, High Street\nColombo 03\nSri Lanka",
            'primary_contact_name' => 'John Doe',
            'primary_contact_designation' => 'Marketing Director',
            'primary_contact_mobile' => '0771234567',
            'primary_contact_email' => 'john.doe@acme.com',
        ]);

        $deal = Deal::create([
            'title' => 'Brand Campaign 2026',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'customer_name' => $customer->name,
            'revenue' => 250000.00,
            'contribution' => 200000.00,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Working on pitch',
            'close_date' => now()->toDateString(),
        ]);

        $response = $this->postJson("/deals/{$deal->id}/create-estimate");

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'redirect']);

        $this->assertDatabaseHas('quotations', [
            'deal_id' => $deal->id,
            'customer_id' => $customer->id,
            'brand_name' => 'Acme Brand',
            'attention_to' => 'John Doe',
            'designation' => 'Marketing Director',
            'address_line_1' => 'No 123, High Street',
            'address_line_2' => 'Colombo 03',
            'address_line_3' => 'Sri Lanka',
            'heading' => 'Brand Campaign 2026',
            'total_amount' => 250000.00,
        ]);

        $estimate = Estimate::where('deal_id', $deal->id)->first();
        $this->assertNotNull($estimate);
        $response->assertJson([
            'redirect' => route('estimates.edit', $estimate->id),
        ]);

        // Verify the edit view renders with the pre-populated values
        $editResponse = $this->get(route('estimates.edit', $estimate->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee($customer->name);
        $editResponse->assertSee('Acme Brand');
        $editResponse->assertSee('John Doe');
        $editResponse->assertSee('Marketing Director');
        $editResponse->assertSee('No 123, High Street');
        $editResponse->assertSee('Colombo 03');
        $editResponse->assertSee('Sri Lanka');
    }

    public function test_create_estimate_falls_back_to_customer_name_when_brand_is_empty(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
            'department' => 'Creative',
        ]);
        $this->actingAs($user);

        $customer = Customer::create([
            'name' => 'Global Imports PLC',
            'brand' => null,
            'email' => 'info@globalimports.com',
            'phone' => '0119876543',
            'billing_address' => '500 Galle Road, Colombo',
            'primary_contact_name' => 'Jane Smith',
            'primary_contact_designation' => 'Brand Executive',
            'primary_contact_mobile' => '0779876543',
            'primary_contact_email' => 'jane@globalimports.com',
        ]);

        $deal = Deal::create([
            'title' => 'Digital Retainer',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'customer_name' => $customer->name,
            'revenue' => 150000.00,
            'contribution' => 120000.00,
            'currency' => 'USD',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Working on pitch',
            'close_date' => now()->toDateString(),
        ]);

        $response = $this->postJson("/deals/{$deal->id}/create-estimate");

        $response->assertStatus(200);

        $this->assertDatabaseHas('quotations', [
            'deal_id' => $deal->id,
            'customer_id' => $customer->id,
            'brand_name' => 'Global Imports PLC',
            'attention_to' => 'Jane Smith',
            'designation' => 'Brand Executive',
            'address_line_1' => '500 Galle Road, Colombo',
            'currency' => 'USD',
        ]);
    }

    public function test_estimate_create_view_prepopulates_details_from_deal_query_param(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
            'department' => 'Creative',
        ]);
        $this->actingAs($user);

        $customer = Customer::create([
            'name' => 'Summit Enterprises Ltd',
            'brand' => 'Summit Peak',
            'email' => 'hello@summit.lk',
            'billing_address' => "45 Union Place\nColombo 02",
            'primary_contact_name' => 'David Perera',
            'primary_contact_designation' => 'Procurement Officer',
        ]);

        $deal = Deal::create([
            'title' => 'Brand Activation 2026',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'customer_name' => $customer->name,
            'revenue' => 300000.00,
            'contribution' => 250000.00,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Working on pitch',
            'close_date' => now()->toDateString(),
        ]);

        $createResponse = $this->get(route('estimates.create', ['deal_id' => $deal->id]));
        $createResponse->assertStatus(200);
        $createResponse->assertSee($customer->name);
        $createResponse->assertSee('Summit Peak');
        $createResponse->assertSee('David Perera');
        $createResponse->assertSee('Procurement Officer');
        $createResponse->assertSee('45 Union Place');
        $createResponse->assertSee('Colombo 02');
    }
}
