<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreUserSeeder extends Seeder
{
    /**
     * Link the users to their stores with roles (spec 07 §3.5).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $memberships = [
                ['admin@acme.test', $fashion->id, StoreUserRole::Owner],
                ['staff@acme.test', $fashion->id, StoreUserRole::Staff],
                ['support@acme.test', $fashion->id, StoreUserRole::Support],
                ['manager@acme.test', $fashion->id, StoreUserRole::Admin],
                ['admin2@acme.test', $electronics->id, StoreUserRole::Owner],
            ];

            foreach ($memberships as [$email, $storeId, $role]) {
                $user = User::query()->where('email', $email)->firstOrFail();

                StoreUser::query()->updateOrCreate(
                    ['store_id' => $storeId, 'user_id' => $user->id],
                    ['role' => $role],
                );
            }
        });
    }
}
