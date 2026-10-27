<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_is_logged_when_user_resets_password_via_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'alice@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $otp = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($otp),
            'created_at' => now(),
        ]);

        $response = $this->post(route('password.update-otp'), [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'description' => 'Reset password',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_activity_is_logged_when_logged_in_user_changes_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPass123!'),
        ]);

        $response = $this->actingAs($user)->post(route('password.change'), [
            'current_password' => 'CurrentPass123!',
            'password' => 'NewSecretPass123!',
            'password_confirmation' => 'NewSecretPass123!',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'description' => 'Reset password',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        $this->assertTrue(Hash::check('NewSecretPass123!', $user->fresh()->password));
    }

    public function test_activity_is_logged_when_admin_resets_user_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'IT Admin',
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'role' => 'Staff',
            'department' => 'Tech',
            'password' => Hash::make('OldPass123!'),
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => 'Staff',
            'department' => 'Tech',
            'password' => 'AdminResetPass123!',
            'password_confirmation' => 'AdminResetPass123!',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('activities', [
            'user_id' => $admin->id,
            'description' => "Reset password for user: {$targetUser->name}",
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
        ]);

        $this->assertTrue(Hash::check('AdminResetPass123!', $targetUser->fresh()->password));
    }
}
