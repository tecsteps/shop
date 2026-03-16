<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();
        $user = User::first();

        StoreUser::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }
}
