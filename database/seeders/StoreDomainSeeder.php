<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        foreach ([
            [$fashion, 'shop.test', 'storefront', true],
            [$fashion, 'acme-fashion.test', 'storefront', true],
            [$fashion, 'admin.acme-fashion.test', 'admin', false],
            [$electronics, 'acme-electronics.test', 'storefront', true],
        ] as [$store, $hostname, $type, $primary]) {
            StoreDomain::query()->updateOrCreate(['hostname' => $hostname], [
                'store_id' => $store->id,
                'type' => $type,
                'is_primary' => $primary,
                'tls_mode' => 'managed',
            ]);
        }
    }
}
