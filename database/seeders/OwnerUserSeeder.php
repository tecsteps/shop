<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'shop')->first();

        if ($store === null) {
            return;
        }

        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@shop.test'],
            [
                'name' => 'Shop Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $hasMembership = DB::table('store_users')
            ->where('store_id', $store->getKey())
            ->where('user_id', $owner->getKey())
            ->exists();

        if (! $hasMembership) {
            DB::table('store_users')->insert([
                'store_id' => $store->getKey(),
                'user_id' => $owner->getKey(),
                'role' => StoreUserRole::Owner->value,
                'created_at' => now(),
            ]);
        }
    }
}
