<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        DB::table('store_users')->updateOrInsert(
            [
                'store_id' => $store->id,
                'user_id' => $user->id,
            ],
            [
                'role' => StoreUserRole::Owner->value,
                'created_at' => now(),
            ],
        );
    }
}
