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
            ['email' => 'admin@example.com', 'name' => 'Admin User'],
            ['email' => 'admin@acme.test', 'name' => 'Acme Admin'],
        ] as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
