<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@acme.test', 'name' => 'Admin User', 'last_login_at' => now()],
            ['email' => 'staff@acme.test', 'name' => 'Staff User', 'last_login_at' => now()->subDays(2)],
            ['email' => 'support@acme.test', 'name' => 'Support User', 'last_login_at' => now()->subDay()],
            ['email' => 'manager@acme.test', 'name' => 'Store Manager', 'last_login_at' => now()->subDay()],
            ['email' => 'admin2@acme.test', 'name' => 'Admin Two', 'last_login_at' => now()->subDay()],
        ];

        foreach ($users as $data) {
            User::factory()->create([
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => 'password',
                'status' => 'active',
                'last_login_at' => $data['last_login_at'],
            ]);
        }
    }
}
