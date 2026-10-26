<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_deals_index_renders_search_bar_and_filters_results(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);

        $customer1 = Customer::create([
            'name' => 'Acme Corporation',
            'brand' => 'Acme',
            'email' => 'acme@example.com',
            'phone' => '1234567890',
            'status' => 'active'
        ]);

        $customer2 = Customer::create([
            'name' => 'Wayne Enterprises',
            'brand' => 'Wayne',
            'email' => 'wayne@example.com',
            'phone' => '9876543210',
            'status' => 'active'
        ]);

        $deal1 = Deal::create([
            'title' => 'Alpha Redesign Project',
            'customer_id' => $customer1->id,
            'user_id' => $admin->id,
            'customer_name' => $customer1->name,
            'revenue' => 150000,
            'contribution' => 100000,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Planned to Meet',
            'job_number' => 'LOOPS/2026/0101',
            'close_date' => now()->toDateString(),
        ]);

        $deal2 = Deal::create([
            'title' => 'Beta Mobile Application',
            'customer_id' => $customer2->id,
            'user_id' => $admin->id,
            'customer_name' => $customer2->name,
            'revenue' => 250000,
            'contribution' => 180000,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Planned to Meet',
            'job_number' => 'LOOPS/2026/0202',
            'close_date' => now()->toDateString(),
        ]);

        // 1. Visit index with no filter - search bar is present and both deals are visible
        $response = $this->actingAs($admin)->get(route('deals.index'));
        $response->assertStatus(200);
        $response->assertSee('id="deal_search"', false);
        $response->assertSee('Alpha Redesign Project');
        $response->assertSee('Beta Mobile Application');

        // 2. Search by title "Alpha"
        $responseTitle = $this->actingAs($admin)->get(route('deals.index', ['search' => 'Alpha']));
        $responseTitle->assertStatus(200);
        $responseTitle->assertSee('Alpha Redesign Project');
        $responseTitle->assertDontSee('Beta Mobile Application');

        // 3. Search by job number "0202"
        $responseJob = $this->actingAs($admin)->get(route('deals.index', ['search' => '0202']));
        $responseJob->assertStatus(200);
        $responseJob->assertSee('Beta Mobile Application');
        $responseJob->assertDontSee('Alpha Redesign Project');

        // 4. Search by customer name "Wayne"
        $responseCustomer = $this->actingAs($admin)->get(route('deals.index', ['search' => 'Wayne']));
        $responseCustomer->assertStatus(200);
        $responseCustomer->assertSee('Beta Mobile Application');
        $responseCustomer->assertDontSee('Alpha Redesign Project');
    }

    public function test_deals_index_renders_own_deals_only_checkbox_and_filters_results(): void
    {
        $user1 = User::factory()->create([
            'name' => 'Alice User',
            'role' => 'Super Admin'
        ]);

        $user2 = User::factory()->create([
            'name' => 'Bob Colleague',
            'role' => 'Manager'
        ]);

        $customer = Customer::create([
            'name' => 'Acme Corporation',
            'brand' => 'Acme',
            'email' => 'acme@example.com',
            'phone' => '1234567890',
            'status' => 'active'
        ]);

        $dealAlice = Deal::create([
            'title' => 'Alice Special Deal',
            'customer_id' => $customer->id,
            'user_id' => $user1->id,
            'customer_name' => $customer->name,
            'revenue' => 100000,
            'contribution' => 80000,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Planned to Meet',
            'close_date' => now()->toDateString(),
        ]);

        $dealBob = Deal::create([
            'title' => 'Bob Team Deal',
            'customer_id' => $customer->id,
            'user_id' => $user2->id,
            'customer_name' => $customer->name,
            'revenue' => 200000,
            'contribution' => 150000,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Planned to Meet',
            'close_date' => now()->toDateString(),
        ]);

        // 1. Without filter: both deals are visible, checkbox is present and unchecked
        $response = $this->actingAs($user1)->get(route('deals.index'));
        $response->assertStatus(200);
        $response->assertSee('id="own_deals_only"', false);
        $response->assertSee('Own Deals Only');
        $response->assertSee('Alice Special Deal');
        $response->assertSee('Bob Team Deal');

        // 2. With own_deals_only=1: only Alice's deal is visible, Bob's deal is hidden, checkbox is checked
        $responseFiltered = $this->actingAs($user1)->get(route('deals.index', ['own_deals_only' => '1']));
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertSee('Alice Special Deal');
        $responseFiltered->assertDontSee('Bob Team Deal');
        $responseFiltered->assertSee('checked', false);
        $responseFiltered->assertSee('Reset');
    }

    public function test_hod_filters_own_deals_only_excluding_subordinate_deals(): void
    {
        $hod = User::factory()->create([
            'name' => 'Department Lead',
            'role' => 'HOD',
            'department' => 'Tech'
        ]);

        $subordinate = User::factory()->create([
            'name' => 'Subordinate Tech Manager',
            'role' => 'Manager',
            'department' => 'Tech',
            'supervisor_id' => $hod->id
        ]);

        $customer = Customer::create([
            'name' => 'Acme Corporation',
            'brand' => 'Acme',
            'email' => 'acme@example.com',
            'phone' => '1234567890',
            'status' => 'active'
        ]);

        $dealHod = Deal::create([
            'title' => 'HOD Direct Project',
            'customer_id' => $customer->id,
            'user_id' => $hod->id,
            'customer_name' => $customer->name,
            'revenue' => 120000,
            'contribution' => 90000,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Planned to Meet',
            'close_date' => now()->toDateString(),
        ]);

        $dealSubordinate = Deal::create([
            'title' => 'Subordinate Managed Project',
            'customer_id' => $customer->id,
            'user_id' => $subordinate->id,
            'customer_name' => $customer->name,
            'revenue' => 180000,
            'contribution' => 130000,
            'currency' => 'LKR',
            'pipeline' => 'Sales Pipeline',
            'stage' => 'Planned to Meet',
            'close_date' => now()->toDateString(),
        ]);

        // Default: HOD sees both own deal and subordinate's deal
        $responseDefault = $this->actingAs($hod)->get(route('deals.index'));
        $responseDefault->assertStatus(200);
        $responseDefault->assertSee('HOD Direct Project');
        $responseDefault->assertSee('Subordinate Managed Project');

        // With own_deals_only=1: HOD sees only their own deal
        $responseFiltered = $this->actingAs($hod)->get(route('deals.index', ['own_deals_only' => '1']));
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertSee('HOD Direct Project');
        $responseFiltered->assertDontSee('Subordinate Managed Project');
    }
}
