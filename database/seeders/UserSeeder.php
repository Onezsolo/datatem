<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $developerEmail = trim((string) env('SEED_DEVELOPER_EMAIL', ''));
        $developerPassword = (string) env('SEED_DEVELOPER_PASSWORD', '');

        if ($developerEmail !== '' && $developerPassword !== '') {
            User::updateOrCreate(
                ['email' => $developerEmail],
                [
                    'full_name' => env('SEED_DEVELOPER_NAME', 'Template Developer'),
                    'phone' => env('SEED_DEVELOPER_PHONE', '0500000002'),
                    'balance' => 0,
                    'password' => Hash::make($developerPassword),
                    'role' => User::ROLE_DEVELOPER,
                ]
            );

            $this->command?->info('Developer account seeded from SEED_DEVELOPER_EMAIL.');
        } elseif ($this->isLocal()) {
            User::updateOrCreate(
                ['email' => 'developer@example.com'],
                [
                    'full_name' => 'Local Developer',
                    'phone' => '05'.random_int(100000000, 999999999),
                    'balance' => 0,
                    'password' => Hash::make('devpassword123'),
                    'role' => User::ROLE_DEVELOPER,
                ]
            );

            $this->command?->warn('Local developer seeded: developer@example.com / devpassword123.');
        }

        $adminEmail = trim((string) env('SEED_ADMIN_EMAIL', ''));
        $adminPassword = (string) env('SEED_ADMIN_PASSWORD', '');

        if ($adminEmail !== '' && $adminPassword !== '') {
            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'full_name' => env('SEED_ADMIN_NAME', 'Administrator'),
                    'phone' => env('SEED_ADMIN_PHONE', '0500000000'),
                    'balance' => 0,
                    'password' => Hash::make($adminPassword),
                    'role' => User::ROLE_ADMIN,
                ]
            );

            $this->command?->info('Admin account seeded from SEED_ADMIN_EMAIL.');
        } elseif ($this->isLocal()) {
            User::updateOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'full_name' => 'Local Admin',
                    'phone' => '0500000000',
                    'balance' => 1000,
                    'password' => Hash::make('admin123'),
                    'role' => User::ROLE_ADMIN,
                ]
            );

            $this->command?->warn('Local admin seeded: admin@example.com / admin123. Change it before going live.');
        } else {
            $this->command?->warn('No admin account seeded. Set SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD.');
        }

        if ($this->isLocal()) {
            User::updateOrCreate(
                ['email' => 'demo@example.com'],
                [
                    'full_name' => 'Demo User',
                    'phone' => '0240000000',
                    'balance' => 50,
                    'password' => Hash::make('password123'),
                    'role' => User::ROLE_USER,
                ]
            );
        }
    }

    private function isLocal(): bool
    {
        return app()->environment('local', 'testing');
    }
}
