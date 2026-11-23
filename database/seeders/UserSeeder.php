<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeded admin logins (change in production):
     * - r2bmarketservices@gmail.com / adminpassword
     * - admin@example.com / admin123
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'full_name' => 'Demo User',
                'phone' => '0240000000',
                'balance' => 50.00,
                'password' => Hash::make('password123'),
                'role' => User::ROLE_USER,
            ]
        );

        $admins = [
            [
                'email' => 'admin@r2bmarket.com',
                'full_name' => 'Admin R2BMarket',
                'phone' => '0500000000',
                'password' => 'adminpassword',
            ],
            [
                'email' => 'admin@example.com',
                'full_name' => 'Admin Demo',
                'phone' => '0500000001',
                'password' => 'admin123',
            ],
        ];

        foreach ($admins as $admin) {
            $password = $admin['password'];
            unset($admin['password']);
            User::updateOrCreate(
                ['email' => $admin['email']],
                array_merge($admin, [
                    'balance' => 1000.00,
                    'password' => Hash::make($password),
                    'role' => User::ROLE_ADMIN,
                ])
            );
        }
    }
}
