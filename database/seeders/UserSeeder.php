<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'email' => 'admin@acme.test',
            'name' => 'Admin User',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        User::factory()->create([
            'email' => 'staff@acme.test',
            'name' => 'Staff User',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDays(2),
        ]);

        User::factory()->create([
            'email' => 'support@acme.test',
            'name' => 'Support User',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);

        User::factory()->create([
            'email' => 'manager@acme.test',
            'name' => 'Store Manager',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);

        User::factory()->create([
            'email' => 'admin2@acme.test',
            'name' => 'Admin Two',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);
    }
}
