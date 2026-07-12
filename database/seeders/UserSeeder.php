<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['admin@acme.test', 'Admin User', now()],
            ['staff@acme.test', 'Staff User', now()->subDays(2)],
            ['support@acme.test', 'Support User', now()->subDay()],
            ['manager@acme.test', 'Store Manager', now()->subDay()],
            ['admin2@acme.test', 'Admin Two', now()->subDay()],
        ] as [$email, $name, $lastLogin]) {
            User::query()->updateOrCreate(['email' => $email], [
                'name' => $name,
                'password_hash' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'last_login_at' => $lastLogin,
            ]);
        }
    }
}
