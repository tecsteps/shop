<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Create the admin/staff user accounts (spec 07 §3.4).
     * All accounts use the password "password".
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $passwordHash = Hash::make('password');

            $users = [
                ['admin@acme.test', 'Admin User', now()],
                ['staff@acme.test', 'Staff User', now()->subDays(2)],
                ['support@acme.test', 'Support User', now()->subDay()],
                ['manager@acme.test', 'Store Manager', now()->subDay()],
                ['admin2@acme.test', 'Admin Two', now()->subDay()],
            ];

            foreach ($users as [$email, $name, $lastLoginAt]) {
                // forceFill: email_verified_at/last_login_at are not mass assignable.
                User::query()->firstOrNew(['email' => $email])
                    ->forceFill([
                        'name' => $name,
                        'password_hash' => $passwordHash,
                        'status' => 'active',
                        'email_verified_at' => now(),
                        'last_login_at' => $lastLoginAt,
                    ])
                    ->save();
            }
        });
    }
}
