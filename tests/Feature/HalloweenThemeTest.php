<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Estimate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalloweenThemeTest extends TestCase
{
    use RefreshDatabase;

    private User $itAdmin;
    private User $financeAdmin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itAdmin = User::factory()->create([
            'email' => 'itadmin@loops.test',
            'role' => 'IT Admin',
            'department' => 'Tech',
        ]);

        $this->financeAdmin = User::factory()->create([
            'email' => 'finance@loops.test',
            'role' => 'Finance Admin',
            'department' => 'Finance',
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'staff@loops.test',
            'role' => 'Staff',
            'department' => 'Creative',
        ]);
    }

    public function test_it_admin_can_update_halloween_theme_setting(): void
    {
        $response = $this->actingAs($this->itAdmin)
            ->post(route('settings.halloween.update'), [
                'halloween_theme_enabled' => '1',
            ]);

        $response->assertRedirect(route('settings.index', ['section' => 'seasonal-theme']));
        $this->assertTrue(Setting::isHalloweenTheme());

        // Now turn it off
        $response = $this->actingAs($this->itAdmin)
            ->post(route('settings.halloween.update'), []);

        $response->assertRedirect(route('settings.index', ['section' => 'seasonal-theme']));
        $this->assertFalse(Setting::isHalloweenTheme());
    }

    public function test_it_admin_can_toggle_halloween_theme_via_ajax(): void
    {
        // Set initial state to false
        Setting::set('halloween_theme_enabled', false, 'theme');
        $this->assertFalse(Setting::isHalloweenTheme());

        // Toggle on
        $response = $this->actingAs($this->itAdmin)
            ->postJson(route('settings.halloween.toggle'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'enabled' => true,
            ]);
        $this->assertTrue(Setting::isHalloweenTheme());

        // Toggle off
        $response = $this->actingAs($this->itAdmin)
            ->postJson(route('settings.halloween.toggle'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'enabled' => false,
            ]);
        $this->assertFalse(Setting::isHalloweenTheme());
    }

    public function test_non_it_admin_cannot_update_or_toggle_halloween_theme(): void
    {
        Setting::set('halloween_theme_enabled', false, 'theme');

        // Test with Finance Admin (who has general admin privileges, but is not IT Admin)
        $response = $this->actingAs($this->financeAdmin)
            ->postJson(route('settings.halloween.update'), [
                'halloween_theme_enabled' => '1',
            ]);
        $response->assertForbidden();
        $this->assertFalse(Setting::isHalloweenTheme());

        $response = $this->actingAs($this->financeAdmin)
            ->postJson(route('settings.halloween.toggle'));
        $response->assertForbidden();
        $this->assertFalse(Setting::isHalloweenTheme());

        // Test with regular user (blocked by prevent.staff middleware and redirected to dashboard)
        $response = $this->actingAs($this->regularUser)
            ->post(route('settings.halloween.update'), [
                'halloween_theme_enabled' => '1',
            ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertFalse(Setting::isHalloweenTheme());
    }

    public function test_settings_tab_renders_halloween_theme_only_for_it_admin(): void
    {
        // IT Admin sees the button and section
        $response = $this->actingAs($this->itAdmin)->get(route('settings.index'));
        $response->assertOk();
        $response->assertSee('id="btn-seasonal-theme"', false);
        $response->assertSee('id="section-seasonal-theme"', false);

        // Finance Admin has access to settings, but does NOT see seasonal theme
        $response = $this->actingAs($this->financeAdmin)->get(route('settings.index'));
        $response->assertOk();
        $response->assertDontSee('id="btn-seasonal-theme"', false);
        $response->assertDontSee('id="section-seasonal-theme"', false);
    }

    public function test_dashboard_does_not_contain_halloween_settings_controls(): void
    {
        Setting::set('halloween_theme_enabled', true, 'theme');

        $response = $this->actingAs($this->itAdmin)->get(route('dashboard'));
        $response->assertOk();

        // Must NOT show settings/toggle buttons on dashboard or header
        $response->assertDontSee('Halloween: ON 🎃');
        $response->assertDontSee('Halloween: OFF');
        $response->assertDontSee('Toggle Halloween Theme');
        $response->assertDontSee('Seasonal Theme Settings');

        // But DOES show aesthetic theme decorations
        $response->assertSee('images/halloween/card-contribution-bg.jpg');
        $response->assertSee('🎃');

        // When disabled
        Setting::set('halloween_theme_enabled', false, 'theme');
        $response = $this->actingAs($this->itAdmin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('images/halloween/card-contribution-bg.jpg');
        $response->assertDontSee('Halloween: ON 🎃');
        $response->assertDontSee('Halloween: OFF');
    }

    public function test_login_screen_renders_halloween_theme_elements(): void
    {
        Setting::set('halloween_theme_enabled', true, 'theme');

        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('images/halloween/login-bg.jpg');
        $response->assertSee('images/halloween/witch-hat.svg');
        $response->assertSee('images/halloween/spiderweb.svg');
        $response->assertSee('images/halloween/sign-in-btn.png');

        // When disabled
        Setting::set('halloween_theme_enabled', false, 'theme');
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertDontSee('images/halloween/login-bg.jpg');
        $response->assertDontSee('images/halloween/sign-in-btn.png');
    }

    public function test_password_reset_screens_render_halloween_theme_elements(): void
    {
        // When enabled
        Setting::set('halloween_theme_enabled', true, 'theme');

        // 1. Forgot password screen
        $response = $this->get(route('password.request'));
        $response->assertOk();
        $response->assertSee('images/halloween/login-bg.jpg');
        $response->assertSee('images/halloween/witch-hat.svg');
        $response->assertSee('images/halloween/spiderweb.svg');
        $response->assertSee('images/halloween/pumpkin.svg');
        $response->assertSee('images/halloween/send-otp-btn.png');
        $response->assertSee('font-creepster');

        // 2. Reset password OTP screen
        $response = $this->get(route('password.otp', ['email' => 'test@loops.test']));
        $response->assertOk();
        $response->assertSee('images/halloween/login-bg.jpg');
        $response->assertSee('images/halloween/witch-hat.svg');
        $response->assertSee('images/halloween/spiderweb.svg');
        $response->assertSee('images/halloween/pumpkin.svg');
        $response->assertSee('images/halloween/reset-password-btn.png');
        $response->assertSee('font-creepster');

        // When disabled
        Setting::set('halloween_theme_enabled', false, 'theme');

        $response = $this->get(route('password.request'));
        $response->assertOk();
        $response->assertDontSee('images/halloween/login-bg.jpg');
        $response->assertDontSee('images/halloween/witch-hat.svg');
        $response->assertDontSee('images/halloween/send-otp-btn.png');

        $response = $this->get(route('password.otp', ['email' => 'test@loops.test']));
        $response->assertOk();
        $response->assertDontSee('images/halloween/login-bg.jpg');
        $response->assertDontSee('images/halloween/witch-hat.svg');
        $response->assertDontSee('images/halloween/reset-password-btn.png');
    }

    public function test_header_titles_render_with_high_contrast_on_halloween_theme(): void
    {
        Setting::set('halloween_theme_enabled', true, 'theme');

        $response = $this->actingAs($this->itAdmin)->get(route('customers.index'));
        $response->assertOk();
        $response->assertSee('text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.95)]', false);
        $response->assertSee('/* Halloween Header Title & Navigation Text Contrast */', false);
        $response->assertSee('Customers');

        // When disabled
        Setting::set('halloween_theme_enabled', false, 'theme');
        $response = $this->actingAs($this->itAdmin)->get(route('customers.index'));
        $response->assertOk();
        $response->assertSee('text-gray-800 truncate flex items-center gap-2', false);
        $response->assertDontSee('/* Halloween Header Title & Navigation Text Contrast */', false);
    }

    public function test_sidebar_renders_ghost_animation_on_halloween_theme(): void
    {
        // When enabled
        Setting::set('halloween_theme_enabled', true, 'theme');

        $response = $this->actingAs($this->itAdmin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('images/halloween/realistic-ghost.png');
        $response->assertSee('animate-ghost-peek');
        $response->assertSee('@keyframes ghostPeek', false);

        // When disabled
        Setting::set('halloween_theme_enabled', false, 'theme');

        $response = $this->actingAs($this->itAdmin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('images/halloween/realistic-ghost.png');
        $response->assertDontSee('animate-ghost-peek');
    }

    public function test_estimate_status_change_to_ready_to_invoice_triggers_halloween_celebration(): void
    {
        Setting::set('halloween_theme_enabled', true, 'theme');

        $customer = Customer::create([
            'name' => 'Spooky Corp',
            'email' => 'spooky@example.com',
            'brand' => 'Spooky',
        ]);

        $deal = Deal::create([
            'title' => 'Halloween Campaign',
            'customer_id' => $customer->id,
            'user_id' => $this->itAdmin->id,
            'customer_name' => $customer->name,
            'stage' => 'Proposal Sent',
            'revenue' => 50000,
        ]);

        $estimate = Estimate::create([
            'customer_id' => $customer->id,
            'deal_id' => $deal->id,
            'reference_number' => 'EST/2026/0666',
            'brand_name' => 'Spooky',
            'currency' => 'LKR',
            'total_amount' => 50000,
            'status' => 'draft',
            'date' => '2026-10-31',
        ]);

        $estimate->items()->create([
            'description' => 'Halloween Pumpkin Carving',
            'type' => 'item',
            'quantity' => 1,
            'unit_price' => 50000,
            'amount' => 50000,
            'sscl_amount' => 0,
            'vat_amount' => 0,
            'total_with_vat' => 50000,
            'position' => 0,
            'department' => 'Creative',
            'revenue_category' => 'Design',
        ]);

        // When Halloween theme is ON
        $response = $this->actingAs($this->itAdmin)
            ->post(route('estimates.updateStatus', $estimate), [
                'status' => 'ready_to_invoice',
            ]);

        $response->assertRedirect(route('invoices.ready'));
        $response->assertSessionHas('halloween_ready_to_invoice', true);

        // Follow the redirect with session flash and verify realistic evil pumpkin, bat animations, and trick or treat elements render
        $readyPage = $this->actingAs($this->itAdmin)
            ->withSession(['halloween_ready_to_invoice' => true])
            ->get(route('invoices.ready'));

        $readyPage->assertOk();
        $readyPage->assertSee('images/halloween/realistic-evil-pumpkin.png');
        $readyPage->assertSee('images/halloween/realistic-bat-1.png');
        $readyPage->assertSee('TRICK OR TREAT!');
        $readyPage->assertSee('animate-bat-fly-1');
        $readyPage->assertSee('animate-bat-wings');
        $readyPage->assertSee('animate-pumpkin-chuckle');
        $readyPage->assertSee('animate-evil-laugh');
        $readyPage->assertSee('Estimate moved to Ready to Invoice!');

        // When Halloween theme is OFF
        Setting::set('halloween_theme_enabled', false, 'theme');

        $estimate->update(['status' => 'draft']);

        $responseOff = $this->actingAs($this->itAdmin)
            ->post(route('estimates.updateStatus', $estimate), [
                'status' => 'ready_to_invoice',
            ]);

        $responseOff->assertRedirect(route('invoices.ready'));
        $responseOff->assertSessionMissing('halloween_ready_to_invoice');

        $readyPageOff = $this->actingAs($this->itAdmin)->get(route('invoices.ready'));
        $readyPageOff->assertOk();
        $readyPageOff->assertDontSee('images/halloween/realistic-evil-pumpkin.png');
        $readyPageOff->assertDontSee('TRICK OR TREAT!');
        $readyPageOff->assertDontSee('animate-bat-fly-1');
    }

    public function test_staff_dashboard_renders_halloween_theme_elements(): void
    {
        Setting::set('halloween_theme_enabled', true, 'theme');

        // Staff user gets black background & dark theme elements
        $response = $this->actingAs($this->regularUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('images/halloween/pumpkin.svg');
        $response->assertSee('images/halloween/spiderweb.svg');
        $response->assertSee('bg-[#0c0316]');
        $response->assertSee('bg-[#160829]/95');
        $response->assertSee('border-orange-500/30');

        // Non-staff user (IT Admin) does NOT get black background
        $adminResponse = $this->actingAs($this->itAdmin)->get(route('dashboard'));
        $adminResponse->assertOk();
        $adminResponse->assertDontSee('bg-[#0c0316]');

        // When Halloween theme is OFF
        Setting::set('halloween_theme_enabled', false, 'theme');
        $responseOff = $this->actingAs($this->regularUser)->get(route('dashboard'));
        $responseOff->assertOk();
        $responseOff->assertDontSee('bg-[#0c0316]');
        $responseOff->assertDontSee('bg-[#160829]/95');
    }
}
