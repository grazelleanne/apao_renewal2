<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (!$email || !$password) {
            $this->command?->warn('Super admin not seeded: set SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD, or run super-admin:create.');
            return;
        }

        $admin = [
            'name' => env('SUPER_ADMIN_NAME', 'APAO Super Administrator'),
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('users', 'is_active')) {
            $admin['is_active'] = true;
        }

        if (Schema::hasColumn('users', 'status')) {
            $admin['status'] = 'Active';
        }

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            $admin + ['created_at' => now()]
        );
    }
}
