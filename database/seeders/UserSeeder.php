<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@acme.test'],
            [
                'name' => 'Acme Admin',
                'password' => 'password',
                'status' => 'active',
                'is_platform_admin' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subDay(),
            ],
        );
    }
}
