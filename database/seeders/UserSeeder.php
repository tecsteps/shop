<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
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

        foreach ($users as $attributes) {
            $user = User::query()->firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => 'password',
                    'status' => UserStatus::Active,
                ]
            );

            $user->forceFill([
                'name' => $attributes['name'],
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'last_login_at' => $attributes['last_login_at'],
            ])->save();
        }
    }
}
