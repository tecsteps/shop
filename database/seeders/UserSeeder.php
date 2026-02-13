<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table with admin and staff accounts.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            /** @var array<int, array{email: string, name: string, last_login_at: \Illuminate\Support\Carbon}> $users */
            $users = [
                ['email' => 'admin@acme.test', 'name' => 'Admin User', 'last_login_at' => now()],
                ['email' => 'staff@acme.test', 'name' => 'Staff User', 'last_login_at' => now()->subDays(2)],
                ['email' => 'support@acme.test', 'name' => 'Support User', 'last_login_at' => now()->subDay()],
                ['email' => 'manager@acme.test', 'name' => 'Store Manager', 'last_login_at' => now()->subDay()],
                ['email' => 'admin2@acme.test', 'name' => 'Admin Two', 'last_login_at' => now()->subDay()],
            ];

            foreach ($users as $userData) {
                User::firstOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'password_hash' => Hash::make('password'),
                        'status' => 'active',
                        'last_login_at' => $userData['last_login_at'],
                    ],
                );
            }
        });
    }
}
