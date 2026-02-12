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
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');
        $electronics = Store::query()->firstWhere('handle', 'acme-electronics');

        $assignments = [
            ['email' => 'admin@acme.test', 'store' => $fashion, 'role' => 'owner'],
            ['email' => 'staff@acme.test', 'store' => $fashion, 'role' => 'staff'],
            ['email' => 'support@acme.test', 'store' => $fashion, 'role' => 'support'],
            ['email' => 'manager@acme.test', 'store' => $fashion, 'role' => 'admin'],
            ['email' => 'admin2@acme.test', 'store' => $electronics, 'role' => 'owner'],
        ];

        foreach ($assignments as $assignment) {
            $user = User::query()->firstWhere('email', $assignment['email']);
            $store = $assignment['store'];

            if (! $user || ! $store) {
                continue;
            }

            StoreUser::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => $assignment['role'],
                    'created_at' => now(),
                ]
            );
        }
    }
}
