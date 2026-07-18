<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->first() ?? Store::factory()->create();
        $user = User::query()->first() ?? User::factory()->create();

        StoreUser::query()->firstOrCreate(
            [
                'store_id' => $store->id,
                'user_id' => $user->id,
            ],
            ['role' => StoreUserRole::Owner],
        );
    }
}
