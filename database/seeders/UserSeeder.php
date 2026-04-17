<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@acme.test', 'last_login_at' => now()],
            ['name' => 'Staff User', 'email' => 'staff@acme.test', 'last_login_at' => now()->subDays(2)],
            ['name' => 'Support User', 'email' => 'support@acme.test', 'last_login_at' => now()->subDay()],
            ['name' => 'Store Manager', 'email' => 'manager@acme.test', 'last_login_at' => now()->subDay()],
            ['name' => 'Admin Two', 'email' => 'admin2@acme.test', 'last_login_at' => now()->subDay()],
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'status' => 'active',
                'last_login_at' => $userData['last_login_at'],
            ]);
        }
    }
}
