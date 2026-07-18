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
    public function run(): void
    {
        DB::transaction(function (): void {
            $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');
            $users = User::query()->whereIn('email', [
                'admin@acme.test',
                'staff@acme.test',
                'support@acme.test',
                'manager@acme.test',
                'admin2@acme.test',
            ])->get()->keyBy('email');

            foreach ([
                ['admin@acme.test', 'acme-fashion', StoreUserRole::Owner],
                ['staff@acme.test', 'acme-fashion', StoreUserRole::Staff],
                ['support@acme.test', 'acme-fashion', StoreUserRole::Support],
                ['manager@acme.test', 'acme-fashion', StoreUserRole::Admin],
                ['admin2@acme.test', 'acme-electronics', StoreUserRole::Owner],
            ] as [$email, $handle, $role]) {
                StoreUser::query()->updateOrCreate(
                    [
                        'store_id' => $stores->get($handle)->id,
                        'user_id' => $users->get($email)->id,
                    ],
                    ['role' => $role],
                );
            }
        });
    }
}
