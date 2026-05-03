<?php

namespace Database\Seeders;

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

        foreach (['shop.test', 'acme-fashion.test'] as $index => $hostname) {
            StoreDomain::query()->updateOrCreate(
                ['hostname' => $hostname],
                [
                    'store_id' => $store->getKey(),
                    'type' => 'storefront',
                    'is_primary' => $index === 0,
                    'tls_mode' => 'managed',
                ],
            );
        }
    }
}
