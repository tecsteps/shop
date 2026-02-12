<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@acme.test',
                'name' => 'Admin User',
                'password' => 'password',
                'email_verified_at' => now(),
                'last_login_at' => now(),
            ],
            [
                'email' => 'staff@acme.test',
                'name' => 'Staff User',
                'password' => 'password',
                'email_verified_at' => now(),
                'last_login_at' => now()->subDays(2),
            ],
            [
                'email' => 'support@acme.test',
                'name' => 'Support User',
                'password' => 'password',
                'email_verified_at' => now(),
                'last_login_at' => now()->subDay(),
            ],
            [
                'email' => 'manager@acme.test',
                'name' => 'Store Manager',
                'password' => 'password',
                'email_verified_at' => now(),
                'last_login_at' => now()->subDay(),
            ],
            [
                'email' => 'admin2@acme.test',
                'name' => 'Admin Two',
                'password' => 'password',
                'email_verified_at' => now(),
                'last_login_at' => now()->subDay(),
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData,
            );
        }
    }
}
