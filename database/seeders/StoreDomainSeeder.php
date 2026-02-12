<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');
        $electronics = Store::query()->firstWhere('handle', 'acme-electronics');

        if ($fashion) {
            StoreDomain::query()->updateOrCreate(
                ['hostname' => 'acme-fashion.test'],
                [
                    'store_id' => $fashion->id,
                    'type' => 'storefront',
                    'is_primary' => true,
                    'tls_mode' => 'managed',
                ]
            );

            StoreDomain::query()->updateOrCreate(
                ['hostname' => 'admin.acme-fashion.test'],
                [
                    'store_id' => $fashion->id,
                    'type' => 'admin',
                    'is_primary' => false,
                    'tls_mode' => 'managed',
                ]
            );
        }

        if ($electronics) {
            StoreDomain::query()->updateOrCreate(
                ['hostname' => 'acme-electronics.test'],
                [
                    'store_id' => $electronics->id,
                    'type' => 'storefront',
                    'is_primary' => true,
                    'tls_mode' => 'managed',
                ]
            );
        }
    }
}
