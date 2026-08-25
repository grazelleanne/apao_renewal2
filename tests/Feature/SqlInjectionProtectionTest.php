<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlInjectionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_filters_treat_sql_injection_payloads_as_data(): void
    {
        DB::table('audit_logs')->insert([
            [
                'user_name' => 'Administrator',
                'user_role' => 'admin',
                'action' => 'login',
                'description' => '{}',
                'created_at' => now(),
            ],
            [
                'user_name' => 'Staff Member',
                'user_role' => 'staff',
                'action' => 'profile_updated',
                'description' => '{}',
                'created_at' => now(),
            ],
        ]);

        $session = ['user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin']];
        $payload = "' OR 1=1 --";

        $this->withSession($session)
            ->getJson('/admin/audit-log-data?action='.urlencode($payload))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withSession($session)
            ->getJson('/admin/audit-log-data?user_name='.urlencode($payload))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_audit_filters_reject_non_scalar_and_invalid_date_inputs(): void
    {
        $session = ['user' => ['id' => 1, 'name' => 'Admin', 'role' => 'admin']];

        $this->withSession($session)
            ->getJson('/admin/audit-log-data?action[malicious]=value')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');

        $this->withSession($session)
            ->getJson('/admin/audit-log-data?date_from=2026-01-01%27%20OR%201%3D1--')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_from');
    }
}
