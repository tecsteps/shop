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
        $domainsByStore = [
            'acme-fashion' => ['shop.test', 'acme-fashion.test'],
            'acme-electronics' => ['acme-electronics.test'],
        ];

        foreach ($domainsByStore as $storeHandle => $hostnames) {
            $store = Store::query()->where('handle', $storeHandle)->firstOrFail();

            foreach ($hostnames as $index => $hostname) {
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
}
