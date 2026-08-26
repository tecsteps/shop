<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Create the admin/staff user accounts for the demo platform.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $users = [
                [
                    'email' => 'admin@acme.test',
                    'name' => 'Admin User',
                    'status' => 'active',
                    'last_login_at' => now(),
                ],
                [
                    'email' => 'staff@acme.test',
                    'name' => 'Staff User',
                    'status' => 'active',
                    'last_login_at' => now()->subDays(2),
                ],
                [
                    'email' => 'support@acme.test',
                    'name' => 'Support User',
                    'status' => 'active',
                    'last_login_at' => now()->subDay(),
                ],
                [
                    'email' => 'manager@acme.test',
                    'name' => 'Store Manager',
                    'status' => 'active',
                    'last_login_at' => now()->subDay(),
                ],
                [
                    'email' => 'admin2@acme.test',
                    'name' => 'Admin Two',
                    'status' => 'active',
                    'last_login_at' => now()->subDay(),
                ],
                [
                    'email' => 'admin@example.com',
                    'name' => 'Demo Admin',
                    'status' => 'active',
                    'last_login_at' => now(),
                ],
            ];

            foreach ($users as $user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'password_hash' => Hash::make('password'),
                        'status' => $user['status'],
                        'last_login_at' => $user['last_login_at'],
                    ],
                );
            }
        });
    }
}
