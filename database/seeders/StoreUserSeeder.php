<?php

namespace Database\Seeders;

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
        $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

        Store::query()->each(function (Store $store) use ($user): void {
            DB::table('store_users')->updateOrInsert(
                [
                    'store_id' => $store->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => 'owner',
                    'created_at' => now(),
                ],
            );
        });
    }
}
