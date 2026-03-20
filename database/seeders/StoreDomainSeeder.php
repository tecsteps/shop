<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        StoreDomain::factory()->create([
            'store_id' => $fashion->id,
            'hostname' => 'acme-fashion.test',
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $fashion->id,
            'hostname' => 'shop.test',
            'type' => 'storefront',
            'is_primary' => false,
            'tls_mode' => 'managed',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $fashion->id,
            'hostname' => 'admin.acme-fashion.test',
            'type' => 'admin',
            'is_primary' => false,
            'tls_mode' => 'managed',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $electronics->id,
            'hostname' => 'acme-electronics.test',
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
        ]);
    }
}
