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
        $email = env('DEFAULT_ADMIN_EMAIL', 'admin@apao.local');

        $admin = [
            'name' => env('DEFAULT_ADMIN_NAME', 'APAO Administrator'),
            'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD', 'Admin@12345')),
            'role' => 'admin',
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
