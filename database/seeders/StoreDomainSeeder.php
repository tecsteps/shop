<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => 'storefront',
            'is_primary' => true,
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => 'admin.shop.test',
            'type' => 'admin',
            'is_primary' => false,
        ]);
    }
}
