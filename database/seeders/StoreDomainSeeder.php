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
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        StoreDomain::create([
            'store_id' => $store->id,
            'domain' => 'shop.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);
    }
}
