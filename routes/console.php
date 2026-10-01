<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('super-admin:create {email} {--name=APAO Super Administrator} {--password=}', function () {
    $email = strtolower(trim((string) $this->argument('email')));
    $name = trim((string) $this->option('name'));
    $password = (string) ($this->option('password') ?: $this->secret('Password'));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('A valid email address is required.');
        return Command::FAILURE;
    }

    if (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)) {
        $this->error('Password must be at least 12 characters and include uppercase, lowercase, number, and symbol.');
        return Command::FAILURE;
    }

    $user = User::updateOrCreate(
        ['email' => $email],
        ['name' => $name, 'password' => Hash::make($password), 'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]
    );

    $this->info("Super administrator ready: {$user->email}");
    return Command::SUCCESS;
})->purpose('Create or securely update the system super administrator');
