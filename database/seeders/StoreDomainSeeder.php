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
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

        StoreDomain::create([
            'store_id' => $fashion->id,
            'hostname' => 'acme-fashion.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);

        StoreDomain::create([
            'store_id' => $fashion->id,
            'hostname' => 'shop.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => false,
        ]);

        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        StoreDomain::create([
            'store_id' => $electronics->id,
            'hostname' => 'acme-electronics.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);
    }
}
