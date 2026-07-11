<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $passwordHash = Hash::make('password');
            foreach ([
                ['email' => 'admin@acme.test', 'name' => 'Admin User', 'last_login_at' => now()],
                ['email' => 'staff@acme.test', 'name' => 'Staff User', 'last_login_at' => now()->subDays(2)],
                ['email' => 'support@acme.test', 'name' => 'Support User', 'last_login_at' => now()->subDay()],
                ['email' => 'manager@acme.test', 'name' => 'Store Manager', 'last_login_at' => now()->subDay()],
                ['email' => 'admin2@acme.test', 'name' => 'Admin Two', 'last_login_at' => now()->subDay()],
            ] as $user) {
                User::query()->updateOrCreate(
                    ['email' => $user['email']],
                    [...$user, 'password_hash' => $passwordHash, 'status' => 'active', 'email_verified_at' => now()],
                );
            }
        });
    }
}
