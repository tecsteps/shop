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
        $assignments = [
            ['email' => 'admin@acme.test', 'handle' => 'acme-fashion', 'role' => StoreUserRole::Owner],
            ['email' => 'staff@acme.test', 'handle' => 'acme-fashion', 'role' => StoreUserRole::Staff],
            ['email' => 'support@acme.test', 'handle' => 'acme-fashion', 'role' => StoreUserRole::Support],
            ['email' => 'manager@acme.test', 'handle' => 'acme-fashion', 'role' => StoreUserRole::Admin],
            ['email' => 'admin2@acme.test', 'handle' => 'acme-electronics', 'role' => StoreUserRole::Owner],
        ];

        foreach ($assignments as $assignment) {
            $user = User::query()->where('email', $assignment['email'])->firstOrFail();
            $store = Store::query()->where('handle', $assignment['handle'])->firstOrFail();

            StoreUser::query()->updateOrCreate(
                ['store_id' => $store->getKey(), 'user_id' => $user->getKey()],
                ['role' => $assignment['role']]
            );
        }
    }
}
