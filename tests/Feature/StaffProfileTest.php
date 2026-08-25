<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_uses_the_same_profile_and_password_rules(): void
    {
        $staff = $this->createStaff();

        $this->withSession(['user' => (array) $staff])->putJson('/staff/profile', [
            'name' => 'Updated Staff',
            'email' => 'staff@example.com',
        ])->assertOk()->assertJsonPath('user.name', 'Updated Staff');

        $this->assertSame('Updated Staff', session('user')['name']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile_updated', 'user_role' => 'staff']);

        $this->withSession(['user' => session('user')])->putJson('/staff/profile/password', [
            'current_password' => 'Correct!Pass1',
            'new_password' => 'weak',
            'new_password_confirmation' => 'weak',
        ])->assertUnprocessable()->assertJsonValidationErrors('new_password');
    }

    public function test_staff_email_change_requires_current_password(): void
    {
        $staff = $this->createStaff();

        $this->withSession(['user' => (array) $staff])->putJson('/staff/profile', [
            'name' => 'Staff User',
            'email' => 'changed@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    private function createStaff(): object
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => Hash::make('Correct!Pass1'),
            'role' => 'staff',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('users')->where('id', $id)->first();
    }
}
