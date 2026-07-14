<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreUserSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        foreach ([
            ['admin@acme.test', $fashion, 'owner'],
            ['staff@acme.test', $fashion, 'staff'],
            ['support@acme.test', $fashion, 'support'],
            ['manager@acme.test', $fashion, 'admin'],
            ['admin2@acme.test', $electronics, 'owner'],
        ] as [$email, $store, $role]) {
            $user = User::query()->where('email', $email)->firstOrFail();
            DB::table('store_users')->updateOrInsert(
                ['store_id' => $store->id, 'user_id' => $user->id],
                ['role' => $role, 'created_at' => now()],
            );
        }
    }
}
