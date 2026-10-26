<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SriLankanTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_timezone_is_asia_colombo(): void
    {
        $this->assertEquals('Asia/Colombo', config('app.timezone'));
        $this->assertEquals('Asia/Colombo', date_default_timezone_get());
        $this->assertEquals('Asia/Colombo', now()->timezoneName);
    }

    public function test_activity_log_displays_sri_lankan_time(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'IT Admin',
        ]);

        $activity = Activity::create([
            'user_id' => $admin->id,
            'description' => 'Tested Sri Lankan Timezone Activity',
            'ip_address' => '127.0.0.1',
        ]);

        $expectedFormattedTime = $activity->created_at->format('M d, Y H:i:s');

        $response = $this->actingAs($admin)->get(route('activities.index'));

        $response->assertOk();
        $response->assertSee($expectedFormattedTime);
    }
}
