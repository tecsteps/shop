<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['email' => 'admin@acme.test', 'name' => 'Admin User', 'last_login_at' => now()],
            ['email' => 'staff@acme.test', 'name' => 'Staff User', 'last_login_at' => now()->subDays(2)],
            ['email' => 'support@acme.test', 'name' => 'Support User', 'last_login_at' => now()->subDay()],
            ['email' => 'manager@acme.test', 'name' => 'Store Manager', 'last_login_at' => now()->subDay()],
            ['email' => 'admin2@acme.test', 'name' => 'Admin Two', 'last_login_at' => now()->subDay()],
        ] as $user) {
            $password = Hash::make('password');

            User::query()->updateOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'password' => $password, 'password_hash' => $password, 'status' => 'active', 'is_platform_admin' => $user['email'] === 'admin@acme.test', 'email_verified_at' => now(), 'last_login_at' => $user['last_login_at']],
            );
        }
    }
}
