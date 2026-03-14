<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => 'acme-fashion.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => false,
        ]);
    }
}
