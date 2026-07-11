<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreUser;
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
        DB::transaction(function (): void {
            $stores = Store::query()->get()->keyBy('handle');
            $users = User::query()->get()->keyBy('email');
            foreach ([
                ['admin@acme.test', 'acme-fashion', 'owner'],
                ['staff@acme.test', 'acme-fashion', 'staff'],
                ['support@acme.test', 'acme-fashion', 'support'],
                ['manager@acme.test', 'acme-fashion', 'admin'],
                ['admin2@acme.test', 'acme-electronics', 'owner'],
            ] as [$email, $handle, $role]) {
                StoreUser::query()->updateOrCreate(
                    ['store_id' => $stores[$handle]->id, 'user_id' => $users[$email]->id],
                    ['role' => $role, 'created_at' => now()],
                );
            }
        });
    }
}
