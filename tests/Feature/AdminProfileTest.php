<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_name_and_session_is_refreshed(): void
    {
        $admin = $this->createUser('Admin User', 'admin@example.com', 'admin');

        $response = $this->withSession(['user' => (array) $admin])->putJson('/admin/profile', [
            'name' => 'Juan Dela Cruz',
            'email' => 'admin@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Juan Dela Cruz')
            ->assertJsonMissingPath('user.password');
        $this->assertSame('Juan Dela Cruz', session('user')['name']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile_updated']);
    }

    public function test_email_must_be_unique_and_requires_current_password(): void
    {
        $admin = $this->createUser('Admin User', 'admin@example.com', 'admin');
        $this->createUser('Other User', 'other@example.com', 'staff');

        $this->withSession(['user' => (array) $admin])->putJson('/admin/profile', [
            'name' => 'Admin User',
            'email' => 'other@example.com',
            'current_password' => 'Correct!Pass1',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->withSession(['user' => (array) $admin])->putJson('/admin/profile', [
            'name' => 'Admin User',
            'email' => 'new@example.com',
            'current_password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_email_change_with_current_password_refreshes_session_and_is_audited(): void
    {
        $admin = $this->createUser('Admin User', 'admin@example.com', 'admin');

        $this->withSession(['user' => (array) $admin])->putJson('/admin/profile', [
            'name' => 'Admin User',
            'email' => 'new@example.com',
            'current_password' => 'Correct!Pass1',
        ])->assertOk()->assertJsonPath('user.email', 'new@example.com');

        $this->assertSame('new@example.com', session('user')['email']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'email_changed']);
    }

    public function test_password_requires_confirmation_and_strength(): void
    {
        $admin = $this->createUser('Admin User', 'admin@example.com', 'admin');

        $this->withSession(['user' => (array) $admin])->putJson('/admin/profile/password', [
            'current_password' => 'Correct!Pass1',
            'new_password' => 'weakpass',
            'new_password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors('new_password');
    }

    public function test_password_is_verified_changed_hashed_and_audited(): void
    {
        $admin = $this->createUser('Admin User', 'admin@example.com', 'admin');

        $this->withSession(['user' => (array) $admin])->putJson('/admin/profile/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'NewSecure!Pass2',
            'new_password_confirmation' => 'NewSecure!Pass2',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $response = $this->withSession(['user' => (array) $admin])->putJson('/admin/profile/password', [
            'current_password' => 'Correct!Pass1',
            'new_password' => 'NewSecure!Pass2',
            'new_password_confirmation' => 'NewSecure!Pass2',
        ]);

        $response->assertOk()->assertJsonMissing(['password' => 'NewSecure!Pass2']);
        $hash = DB::table('users')->where('id', $admin->id)->value('password');
        $this->assertTrue(Hash::check('NewSecure!Pass2', $hash));
        $this->assertNotSame('NewSecure!Pass2', $hash);
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_changed']);
    }

    public function test_non_admin_and_inactive_accounts_are_rejected(): void
    {
        $staff = $this->createUser('Staff User', 'staff@example.com', 'staff');
        $this->withSession(['user' => (array) $staff])->putJson('/admin/profile', [
            'name' => 'Changed', 'email' => 'staff@example.com',
        ])->assertForbidden();

        $inactive = $this->createUser('Inactive Admin', 'inactive@example.com', 'admin', false);
        $this->withSession(['user' => (array) $inactive])->putJson('/admin/profile', [
            'name' => 'Changed', 'email' => 'inactive@example.com',
        ])->assertForbidden();
    }

    private function createUser(string $name, string $email, string $role, bool $active = true): object
    {
        $id = DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Correct!Pass1'),
            'role' => $role,
            'is_active' => $active,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('users')->where('id', $id)->first();
    }
}
