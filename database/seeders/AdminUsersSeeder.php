<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'shop')->first();

        if ($store === null) {
            return;
        }

        $users = [
            ['email' => 'owner@shop.test', 'name' => 'Shop Owner', 'role' => StoreUserRole::Owner],
            ['email' => 'admin@shop.test', 'name' => 'Shop Admin', 'role' => StoreUserRole::Admin],
            ['email' => 'staff@shop.test', 'name' => 'Shop Staff', 'role' => StoreUserRole::Staff],
            ['email' => 'support@shop.test', 'name' => 'Shop Support', 'role' => StoreUserRole::Support],
        ];

        foreach ($users as $entry) {
            $user = User::query()->firstOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $hasMembership = DB::table('store_users')
                ->where('store_id', $store->getKey())
                ->where('user_id', $user->getKey())
                ->exists();

            if (! $hasMembership) {
                DB::table('store_users')->insert([
                    'store_id' => $store->getKey(),
                    'user_id' => $user->getKey(),
                    'role' => $entry['role']->value,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
