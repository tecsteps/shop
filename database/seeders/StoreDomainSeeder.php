<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        StoreDomain::create([
            'store_id' => $fashion->id,
            'hostname' => 'acme-fashion.test',
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
        ]);

        StoreDomain::create([
            'store_id' => $fashion->id,
            'hostname' => 'admin.acme-fashion.test',
            'type' => 'admin',
            'is_primary' => false,
            'tls_mode' => 'managed',
        ]);

        StoreDomain::create([
            'store_id' => $electronics->id,
            'hostname' => 'acme-electronics.test',
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
        ]);

        // Herd local development domain
        StoreDomain::create([
            'store_id' => $fashion->id,
            'hostname' => 'shop.test',
            'type' => 'storefront',
            'is_primary' => false,
            'tls_mode' => 'managed',
        ]);
    }
}
