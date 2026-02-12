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

        // Acme Fashion domains
        StoreDomain::firstOrCreate(
            ['hostname' => 'shop.test'],
            [
                'store_id' => $fashion->id,
                'type' => 'storefront',
                'is_primary' => true,
                'tls_mode' => 'managed',
            ],
        );

        StoreDomain::firstOrCreate(
            ['hostname' => 'acme-fashion.test'],
            [
                'store_id' => $fashion->id,
                'type' => 'storefront',
                'is_primary' => false,
                'tls_mode' => 'managed',
            ],
        );

        StoreDomain::firstOrCreate(
            ['hostname' => 'admin.acme-fashion.test'],
            [
                'store_id' => $fashion->id,
                'type' => 'admin',
                'is_primary' => false,
                'tls_mode' => 'managed',
            ],
        );

        // Acme Electronics domains
        StoreDomain::firstOrCreate(
            ['hostname' => 'acme-electronics.test'],
            [
                'store_id' => $electronics->id,
                'type' => 'storefront',
                'is_primary' => true,
                'tls_mode' => 'managed',
            ],
        );
    }
}
