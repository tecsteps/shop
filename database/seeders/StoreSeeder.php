<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();

        $store = Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => 'acme-fashion.test',
            'type' => 'storefront',
            'is_primary' => true,
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => 'storefront',
            'is_primary' => false,
        ]);

        StoreSettings::factory()->create([
            'store_id' => $store->id,
        ]);

        $user = User::first();
        if ($user) {
            $store->users()->attach($user->id, ['role' => 'owner']);
        }
    }
}
