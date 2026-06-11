<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    /**
     * Link the demo users to their stores with roles.
     */
    public function run(): void
    {
        $assignments = [
            ['email' => 'admin@acme.test', 'handle' => 'acme-fashion', 'role' => 'owner'],
            ['email' => 'staff@acme.test', 'handle' => 'acme-fashion', 'role' => 'staff'],
            ['email' => 'support@acme.test', 'handle' => 'acme-fashion', 'role' => 'support'],
            ['email' => 'manager@acme.test', 'handle' => 'acme-fashion', 'role' => 'admin'],
            ['email' => 'admin2@acme.test', 'handle' => 'acme-electronics', 'role' => 'owner'],
        ];

        foreach ($assignments as $assignment) {
            $user = User::query()->where('email', $assignment['email'])->firstOrFail();
            $store = Store::query()->where('handle', $assignment['handle'])->firstOrFail();

            StoreUser::query()->updateOrCreate(
                ['store_id' => $store->getKey(), 'user_id' => $user->getKey()],
                ['role' => $assignment['role']],
            );
        }
    }
}
