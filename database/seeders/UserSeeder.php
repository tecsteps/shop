<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $admin = User::factory()->create([
            'email' => 'admin@acme.test',
            'name' => 'Admin User',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now(),
        ]);
        $fashion->users()->attach($admin, ['role' => StoreUserRole::Owner]);

        $staff = User::factory()->create([
            'email' => 'staff@acme.test',
            'name' => 'Staff User',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDays(2),
        ]);
        $fashion->users()->attach($staff, ['role' => StoreUserRole::Staff]);

        $support = User::factory()->create([
            'email' => 'support@acme.test',
            'name' => 'Support User',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);
        $fashion->users()->attach($support, ['role' => StoreUserRole::Support]);

        $manager = User::factory()->create([
            'email' => 'manager@acme.test',
            'name' => 'Store Manager',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);
        $fashion->users()->attach($manager, ['role' => StoreUserRole::Admin]);

        $admin2 = User::factory()->create([
            'email' => 'admin2@acme.test',
            'name' => 'Admin Two',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);
        $electronics->users()->attach($admin2, ['role' => StoreUserRole::Owner]);
    }
}
