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
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get();
        $users = User::query()->whereIn('email', ['admin@example.com', 'admin@acme.test'])->get();

        foreach ($stores as $store) {
            foreach ($users as $user) {
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
    }
}
