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
        $fashion = Store::where('slug', 'acme-fashion')->firstOrFail();

        StoreDomain::firstOrCreate(
            ['domain' => 'shop.test'],
            [
                'store_id' => $fashion->id,
                'type' => StoreDomainType::Storefront,
                'is_primary' => true,
            ]
        );

        StoreDomain::firstOrCreate(
            ['domain' => 'admin.acme-fashion.test'],
            [
                'store_id' => $fashion->id,
                'type' => StoreDomainType::Admin,
                'is_primary' => false,
            ]
        );

        $electronics = Store::where('slug', 'acme-electronics')->firstOrFail();

        StoreDomain::firstOrCreate(
            ['domain' => 'acme-electronics.test'],
            [
                'store_id' => $electronics->id,
                'type' => StoreDomainType::Storefront,
                'is_primary' => true,
            ]
        );
    }
}
