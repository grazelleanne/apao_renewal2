<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_admin_but_not_staff_routes(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $session = ['user' => $user->toArray()];

        $this->withSession($session)->get('/admin/dashboard')->assertOk();
        $this->withSession($session)
            ->get('/staff/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_super_admin_command_creates_an_active_super_admin(): void
    {
        $this->artisan('super-admin:create', [
            'email' => 'root@example.com',
            '--name' => 'System Owner',
            '--password' => 'StrongPassword!123',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'root@example.com',
            'name' => 'System Owner',
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }
}
