<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');
        $users = User::query()->whereIn('email', ['admin@acme.test', 'staff@acme.test', 'support@acme.test', 'manager@acme.test', 'admin2@acme.test'])->get()->keyBy('email');

        foreach ([
            ['email' => 'admin@acme.test', 'store' => 'acme-fashion', 'role' => 'owner'],
            ['email' => 'staff@acme.test', 'store' => 'acme-fashion', 'role' => 'staff'],
            ['email' => 'support@acme.test', 'store' => 'acme-fashion', 'role' => 'support'],
            ['email' => 'manager@acme.test', 'store' => 'acme-fashion', 'role' => 'admin'],
            ['email' => 'admin2@acme.test', 'store' => 'acme-electronics', 'role' => 'owner'],
        ] as $membership) {
            $store = $stores->get($membership['store']);
            $user = $users->get($membership['email']);

            if ($store !== null && $user !== null) {
                $store->users()->syncWithoutDetaching([$user->getKey() => ['role' => $membership['role']]]);
            }
        }
    }
}
