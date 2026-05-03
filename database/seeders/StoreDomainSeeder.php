<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        StoreDomain::query()->updateOrCreate(
            ['hostname' => 'shop.test'],
            [
                'store_id' => $store->id,
                'type' => StoreDomainType::Storefront,
                'is_primary' => true,
                'tls_mode' => 'managed',
            ],
        );
    }
}
