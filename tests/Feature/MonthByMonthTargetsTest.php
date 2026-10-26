<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Target;
use App\Models\TargetPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthByMonthTargetsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $salesperson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'IT Admin',
            'department' => 'Tech',
        ]);

        $this->salesperson = User::factory()->create([
            'email' => 'salesperson@test.com',
            'role' => 'Staff',
            'department' => 'AM',
            'name' => 'John Doe',
        ]);
    }

    public function test_admin_can_view_targets_section_with_selected_month_and_year(): void
    {
        TargetPeriod::create([
            'year' => 2026,
            'month' => 10,
            'name' => 'October 2026',
        ]);

        $response = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 10,
        ]));

        $response->assertOk();
        $response->assertSee('Month-by-Month Targets');
        $response->assertSee('October 2026');
        $response->assertSee('Department Targets (LKR)');
        $response->assertSee('User Targets (LKR)');
        $response->assertSee('Apply these department targets to');
        $response->assertSee('Apply these user targets to');
        $response->assertSee('Clear Period');
        $response->assertSee('Clear Dept Targets');
        $response->assertSee('Clear User Targets');
        $response->assertSee('deleteSingleTargetModal');
        $response->assertSee('deletePeriodTargetsModal');
    }

    public function test_targets_sections_are_hidden_when_period_is_not_added(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 1,
        ]));

        $response->assertOk();
        $response->assertDontSee('Department Targets (LKR)');
        $response->assertDontSee('User Targets (LKR)');
        $response->assertSee('is not added as an active period');
        $response->assertSee('Add January 2026 Period');
    }

    public function test_can_update_department_target_for_specific_month(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.updateDepartmentTargets'), [
            'year' => 2026,
            'month' => 5,
            'targets' => [
                'AM' => 15000000.00,
                'BD' => 3500000.00,
            ],
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 5,
        ]));

        $this->assertDatabaseHas('targets', [
            'type' => 'department',
            'department' => 'AM',
            'year' => 2026,
            'month' => 5,
            'target_amount' => 15000000.00,
        ]);

        // Month 6 should not have this target
        $this->assertDatabaseMissing('targets', [
            'type' => 'department',
            'department' => 'AM',
            'year' => 2026,
            'month' => 6,
        ]);

        // Helper check
        $this->assertEquals(15000000.00, Target::getDepartmentTarget('AM', 2026, 5));
    }

    public function test_can_apply_department_target_to_all_12_months(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.updateDepartmentTargets'), [
            'year' => 2026,
            'month' => 1,
            'apply_to_all_months' => '1',
            'targets' => [
                'Tech' => 2500000.00,
            ],
        ]);

        $response->assertRedirect();

        // Check that all 12 months have been created
        for ($m = 1; $m <= 12; $m++) {
            $this->assertDatabaseHas('targets', [
                'type' => 'department',
                'department' => 'Tech',
                'year' => 2026,
                'month' => $m,
                'target_amount' => 2500000.00,
            ]);
            $this->assertEquals(2500000.00, Target::getDepartmentTarget('Tech', 2026, $m));
        }
    }

    public function test_can_update_user_target_for_specific_month(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.updateUserTargets'), [
            'year' => 2026,
            'month' => 8,
            'targets' => [
                $this->salesperson->id => 4500000.00,
            ],
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 8,
        ]));

        $this->assertDatabaseHas('targets', [
            'type' => 'user',
            'user_id' => $this->salesperson->id,
            'year' => 2026,
            'month' => 8,
            'target_amount' => 4500000.00,
        ]);

        $this->assertEquals(4500000.00, Target::getUserTarget($this->salesperson->id, 2026, 8));
    }

    public function test_can_apply_user_target_to_all_12_months(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.updateUserTargets'), [
            'year' => 2027,
            'month' => 3,
            'apply_to_all_months' => '1',
            'targets' => [
                $this->salesperson->id => 3000000.00,
            ],
        ]);

        $response->assertRedirect();

        for ($m = 1; $m <= 12; $m++) {
            $this->assertDatabaseHas('targets', [
                'type' => 'user',
                'user_id' => $this->salesperson->id,
                'year' => 2027,
                'month' => $m,
                'target_amount' => 3000000.00,
            ]);
            $this->assertEquals(3000000.00, Target::getUserTarget($this->salesperson->id, 2027, $m));
        }
    }

    public function test_target_fallback_to_default_when_month_target_is_not_set(): void
    {
        // Create legacy default target with null year and null month
        Target::create([
            'type' => 'department',
            'department' => 'Creative',
            'year' => null,
            'month' => null,
            'target_amount' => 6000000.00,
        ]);

        Target::create([
            'type' => 'user',
            'user_id' => $this->salesperson->id,
            'year' => null,
            'month' => null,
            'target_amount' => 1200000.00,
        ]);

        // When requesting for a month that doesn't have a specific target, should fallback to default
        $this->assertEquals(6000000.00, Target::getDepartmentTarget('Creative', 2026, 11));
        $this->assertEquals(1200000.00, Target::getUserTarget($this->salesperson->id, 2026, 11));

        // When a specific month target is set, it overrides the fallback
        Target::create([
            'type' => 'department',
            'department' => 'Creative',
            'year' => 2026,
            'month' => 11,
            'target_amount' => 7500000.00,
        ]);

        $this->assertEquals(7500000.00, Target::getDepartmentTarget('Creative', 2026, 11));
        // Other month still gets default
        $this->assertEquals(6000000.00, Target::getDepartmentTarget('Creative', 2026, 12));
    }

    public function test_can_delete_single_department_target_for_month(): void
    {
        Target::create([
            'type' => 'department',
            'department' => 'AM',
            'year' => 2026,
            'month' => 6,
            'target_amount' => 12000000.00,
        ]);

        $response = $this->actingAs($this->admin)->post(route('settings.deleteSingleTarget'), [
            'type' => 'department',
            'department' => 'AM',
            'year' => 2026,
            'month' => 6,
            'scope' => 'month',
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 6,
        ]));

        $this->assertEquals(0.00, Target::getDepartmentTarget('AM', 2026, 6));
    }

    public function test_can_delete_single_department_target_completely(): void
    {
        Target::create([
            'type' => 'department',
            'department' => 'Tech',
            'year' => 2026,
            'month' => 7,
            'target_amount' => 5000000.00,
        ]);

        Target::create([
            'type' => 'department',
            'department' => 'Tech',
            'year' => null,
            'month' => null,
            'target_amount' => 3000000.00,
        ]);

        $response = $this->actingAs($this->admin)->post(route('settings.deleteSingleTarget'), [
            'type' => 'department',
            'department' => 'Tech',
            'year' => 2026,
            'month' => 7,
            'scope' => 'all',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('targets', [
            'type' => 'department',
            'department' => 'Tech',
        ]);
        $this->assertEquals(0.00, Target::getDepartmentTarget('Tech', 2026, 7));
    }

    public function test_can_delete_single_user_target_for_month(): void
    {
        Target::create([
            'type' => 'user',
            'user_id' => $this->salesperson->id,
            'year' => 2026,
            'month' => 9,
            'target_amount' => 4000000.00,
        ]);

        $response = $this->actingAs($this->admin)->post(route('settings.deleteSingleTarget'), [
            'type' => 'user',
            'user_id' => $this->salesperson->id,
            'year' => 2026,
            'month' => 9,
            'scope' => 'month',
        ]);

        $response->assertRedirect();
        $this->assertEquals(0.00, Target::getUserTarget($this->salesperson->id, 2026, 9));
    }

    public function test_can_delete_target_period_for_month(): void
    {
        Target::create([
            'type' => 'department',
            'department' => 'BD',
            'year' => 2026,
            'month' => 10,
            'target_amount' => 9000000.00,
        ]);

        Target::create([
            'type' => 'user',
            'user_id' => $this->salesperson->id,
            'year' => 2026,
            'month' => 10,
            'target_amount' => 2000000.00,
        ]);

        $response = $this->actingAs($this->admin)->post(route('settings.deleteTargetPeriod'), [
            'type' => 'all',
            'year' => 2026,
            'month' => 10,
            'scope' => 'month',
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 10,
        ]));

        $this->assertEquals(0.00, Target::getDepartmentTarget('BD', 2026, 10));
        $this->assertEquals(0.00, Target::getUserTarget($this->salesperson->id, 2026, 10));
    }

    public function test_can_delete_target_period_for_entire_year(): void
    {
        Target::create([
            'type' => 'department',
            'department' => 'Corporate',
            'year' => 2026,
            'month' => 4,
            'target_amount' => 1000000.00,
        ]);

        $response = $this->actingAs($this->admin)->post(route('settings.deleteTargetPeriod'), [
            'type' => 'department',
            'year' => 2026,
            'month' => 4,
            'scope' => 'year',
        ]);

        $response->assertRedirect();

        for ($m = 1; $m <= 12; $m++) {
            $this->assertEquals(0.00, Target::getDepartmentTarget('Corporate', 2026, $m));
        }
    }

    public function test_admin_can_add_single_target_period(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2027,
            'month' => 6,
            'prefill' => 'default',
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2027,
            'month' => 6,
        ]));

        $this->assertDatabaseHas('target_periods', [
            'year' => 2027,
            'month' => 6,
        ]);

        $this->assertTrue(TargetPeriod::where('year', 2027)->where('month', 6)->exists());
    }

    public function test_admin_can_add_all_12_target_periods(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2028,
            'month' => 'all',
            'prefill' => 'zero',
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2028,
            'month' => 1,
        ]));

        $this->assertEquals(12, TargetPeriod::where('year', 2028)->count());
        for ($m = 1; $m <= 12; $m++) {
            $this->assertDatabaseHas('target_periods', [
                'year' => 2028,
                'month' => $m,
            ]);
        }
    }

    public function test_admin_can_delete_target_period(): void
    {
        TargetPeriod::create([
            'year' => 2029,
            'month' => 3,
            'name' => 'March 2029',
        ]);

        Target::create([
            'type' => 'department',
            'department' => 'AM',
            'year' => 2029,
            'month' => 3,
            'target_amount' => 5000000.00,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('settings.destroyTargetPeriod'), [
            'year' => 2029,
            'month' => 3,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('target_periods', [
            'year' => 2029,
            'month' => 3,
        ]);

        $this->assertDatabaseMissing('targets', [
            'department' => 'AM',
            'year' => 2029,
            'month' => 3,
        ]);
    }

    public function test_admin_can_delete_all_periods_for_year(): void
    {
        for ($m = 1; $m <= 3; $m++) {
            TargetPeriod::create([
                'year' => 2030,
                'month' => $m,
                'name' => "Month {$m} 2030",
            ]);
        }

        $response = $this->actingAs($this->admin)->delete(route('settings.destroyTargetPeriod'), [
            'year' => 2030,
            'month' => 'all',
        ]);

        $response->assertRedirect();
        $this->assertEquals(0, TargetPeriod::where('year', 2030)->count());
    }

    public function test_target_view_shows_add_period_and_delete_period_elements(): void
    {
        TargetPeriod::create([
            'year' => 2026,
            'month' => 5,
            'name' => 'May 2026',
        ]);

        $response = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertOk();
        $response->assertSee('Add Period');
        $response->assertSee('Delete Period');
        $response->assertSee('Active Target Periods');
        $response->assertSee('addPeriodModal');
        $response->assertSee('destroyPeriodModal');
    }

    public function test_quick_switch_only_shows_added_months(): void
    {
        TargetPeriod::create([
            'year' => 2026,
            'month' => 5,
            'name' => 'May 2026',
        ]);
        TargetPeriod::create([
            'year' => 2026,
            'month' => 8,
            'name' => 'August 2026',
        ]);

        $response = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertOk();
        // May (5) and Aug (8) links should exist in the view
        $response->assertSee('section=targets&amp;year=2026&amp;month=5', false);
        $response->assertSee('section=targets&amp;year=2026&amp;month=8', false);
        // Unadded months (like Jan=1, Feb=2, Dec=12) must not exist in quick switch links
        $response->assertDontSee('section=targets&amp;year=2026&amp;month=1', false);
        $response->assertDontSee('section=targets&amp;year=2026&amp;month=2', false);
        $response->assertDontSee('section=targets&amp;year=2026&amp;month=12', false);
    }

    public function test_targets_section_defaults_to_current_month_and_year_when_no_params_provided(): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $currentMonthName = date('F');

        $response = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
        ]));

        $response->assertOk();
        $response->assertSee("value=\"{$currentYear}\" selected", false);
        $response->assertSee("value=\"{$currentMonth}\" selected", false);
        $response->assertSee("{$currentMonthName} {$currentYear}");
    }

    public function test_total_dept_target_is_zero_when_period_is_not_configured(): void
    {
        // Ensure legacy default exists
        Target::create([
            'type' => 'department',
            'department' => 'Creative',
            'year' => null,
            'month' => null,
            'target_amount' => 5000000.00,
        ]);

        // Access unconfigured period 2026-10 (assuming period not created)
        TargetPeriod::where('year', 2026)->where('month', 10)->delete();

        $response = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 10,
        ]));

        $response->assertOk();
        $response->assertSee('Total Dept Target:');
        $response->assertSee('LKR 0.00');
        $response->assertDontSee('LKR 5,000,000.00');
    }

    public function test_total_dept_target_is_dynamic_sum_of_configured_period(): void
    {
        TargetPeriod::firstOrCreate(['year' => 2026, 'month' => 7], ['name' => 'July 2026']);

        Target::updateOrCreate(
            ['type' => 'department', 'department' => 'Creative', 'year' => 2026, 'month' => 7],
            ['target_amount' => 4500000.00]
        );
        Target::updateOrCreate(
            ['type' => 'department', 'department' => 'Digital', 'year' => 2026, 'month' => 7],
            ['target_amount' => 2500000.00]
        );

        $response = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 7,
        ]));

        $response->assertOk();
        $response->assertSee('Total Dept Target:');
        $response->assertSee('LKR 7,000,000.00');
    }

    public function test_admin_can_select_users_when_adding_target_period_and_unselected_users_are_omitted(): void
    {
        $userA = User::factory()->create(['name' => 'Alice Selected', 'role' => 'Staff', 'department' => 'AM']);
        $userB = User::factory()->create(['name' => 'Bob Selected', 'role' => 'Staff', 'department' => 'BD']);
        $userC = User::factory()->create(['name' => 'Charlie Unselected', 'role' => 'Staff', 'department' => 'Tech']);

        $response = $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 4,
            'prefill' => 'default',
            'users' => [$userA->id, $userB->id],
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 4,
        ]));

        $this->assertDatabaseHas('targets', [
            'type' => 'user',
            'user_id' => $userA->id,
            'year' => 2026,
            'month' => 4,
        ]);
        $this->assertDatabaseHas('targets', [
            'type' => 'user',
            'user_id' => $userB->id,
            'year' => 2026,
            'month' => 4,
        ]);
        $this->assertDatabaseMissing('targets', [
            'type' => 'user',
            'user_id' => $userC->id,
            'year' => 2026,
            'month' => 4,
        ]);

        $this->assertEquals(0.00, Target::getUserTarget($userC->id, 2026, 4));

        $viewResponse = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 4,
        ]));

        $viewResponse->assertOk();
        $viewResponse->assertSee("targets[{$userA->id}]");
        $viewResponse->assertSee("targets[{$userB->id}]");
        $viewResponse->assertDontSee("targets[{$userC->id}]");
    }

    public function test_admin_can_add_users_to_existing_target_period(): void
    {
        $userA = User::factory()->create(['name' => 'Alice Existing', 'role' => 'Staff']);
        $userB = User::factory()->create(['name' => 'Bob AddedLater', 'role' => 'Staff']);

        // First add period with only user A
        $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 5,
            'prefill' => 'zero',
            'users' => [$userA->id],
        ]);

        // Verify user B is not in targets
        $this->assertDatabaseMissing('targets', [
            'type' => 'user',
            'user_id' => $userB->id,
            'year' => 2026,
            'month' => 5,
        ]);

        // Add user B to period
        $response = $this->actingAs($this->admin)->post(route('settings.addUsersToTargetPeriod'), [
            'year' => 2026,
            'month' => 5,
            'user_ids' => [$userB->id],
            'initial_amount' => 'zero',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('targets', [
            'type' => 'user',
            'user_id' => $userB->id,
            'year' => 2026,
            'month' => 5,
        ]);

        // Both users should now be visible in targets grid
        $viewResponse = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 5,
        ]));
        $viewResponse->assertSee("targets[{$userA->id}]");
        $viewResponse->assertSee("targets[{$userB->id}]");
    }

    public function test_deleting_user_target_removes_user_from_targets_grid(): void
    {
        $userA = User::factory()->create(['name' => 'Alice RemoveMe', 'role' => 'Staff']);

        $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 6,
            'prefill' => 'zero',
            'users' => [$userA->id],
        ]);

        $this->assertDatabaseHas('targets', [
            'type' => 'user',
            'user_id' => $userA->id,
            'year' => 2026,
            'month' => 6,
        ]);

        // Delete user target for month
        $response = $this->actingAs($this->admin)->post(route('settings.deleteSingleTarget'), [
            'type' => 'user',
            'user_id' => $userA->id,
            'year' => 2026,
            'month' => 6,
            'scope' => 'month',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('targets', [
            'type' => 'user',
            'user_id' => $userA->id,
            'year' => 2026,
            'month' => 6,
        ]);

        // Should not see Alice RemoveMe in targets grid
        $viewResponse = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 6,
        ]));
        $viewResponse->assertDontSee("targets[{$userA->id}]");
        $viewResponse->assertSee('No Users Selected for Targets');
    }

    public function test_admin_can_select_departments_when_adding_target_period_and_unselected_departments_are_omitted(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 4,
            'prefill' => 'default',
            'departments' => ['Tech', 'Creative'],
        ]);

        $response->assertRedirect(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 4,
        ]));

        $this->assertDatabaseHas('targets', [
            'type' => 'department',
            'department' => 'Tech',
            'year' => 2026,
            'month' => 4,
        ]);
        $this->assertDatabaseHas('targets', [
            'type' => 'department',
            'department' => 'Creative',
            'year' => 2026,
            'month' => 4,
        ]);
        $this->assertDatabaseMissing('targets', [
            'type' => 'department',
            'department' => 'AM',
            'year' => 2026,
            'month' => 4,
        ]);
        $this->assertDatabaseMissing('targets', [
            'type' => 'department',
            'department' => 'Digital',
            'year' => 2026,
            'month' => 4,
        ]);

        $this->assertEquals(0.00, Target::getDepartmentTarget('AM', 2026, 4));

        $viewResponse = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 4,
        ]));

        $viewResponse->assertOk();
        $viewResponse->assertSee('targets[Tech]');
        $viewResponse->assertSee('targets[Creative]');
        $viewResponse->assertDontSee('targets[AM]');
        $viewResponse->assertDontSee('targets[Digital]');
    }

    public function test_admin_can_add_departments_to_existing_target_period(): void
    {
        // First add period with only Tech
        $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 5,
            'prefill' => 'zero',
            'departments' => ['Tech'],
        ]);

        // Verify AM is not in targets
        $this->assertDatabaseMissing('targets', [
            'type' => 'department',
            'department' => 'AM',
            'year' => 2026,
            'month' => 5,
        ]);

        // Add AM to period
        $response = $this->actingAs($this->admin)->post(route('settings.addDepartmentsToTargetPeriod'), [
            'year' => 2026,
            'month' => 5,
            'departments' => ['AM'],
            'initial_amount' => 'zero',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('targets', [
            'type' => 'department',
            'department' => 'AM',
            'year' => 2026,
            'month' => 5,
        ]);

        // Both departments should now be visible in targets grid
        $viewResponse = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 5,
        ]));
        $viewResponse->assertSee('targets[Tech]');
        $viewResponse->assertSee('targets[AM]');
    }

    public function test_deleting_department_target_removes_department_from_targets_grid(): void
    {
        $this->actingAs($this->admin)->post(route('settings.storeTargetPeriod'), [
            'year' => 2026,
            'month' => 6,
            'prefill' => 'zero',
            'departments' => ['Tech'],
        ]);

        $this->assertDatabaseHas('targets', [
            'type' => 'department',
            'department' => 'Tech',
            'year' => 2026,
            'month' => 6,
        ]);

        // Delete department target for month
        $response = $this->actingAs($this->admin)->post(route('settings.deleteSingleTarget'), [
            'type' => 'department',
            'department' => 'Tech',
            'year' => 2026,
            'month' => 6,
            'scope' => 'month',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('targets', [
            'type' => 'department',
            'department' => 'Tech',
            'year' => 2026,
            'month' => 6,
        ]);

        // Should not see Tech in targets grid, and should see empty state
        $viewResponse = $this->actingAs($this->admin)->get(route('settings.index', [
            'section' => 'targets',
            'year' => 2026,
            'month' => 6,
        ]));
        $viewResponse->assertDontSee('targets[Tech]');
        $viewResponse->assertSee('No Departments Selected for Targets');
    }
}

