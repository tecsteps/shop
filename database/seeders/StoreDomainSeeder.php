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
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ([
            ['hostname' => 'shop.test', 'store' => $fashion, 'primary' => true],
            ['hostname' => 'acme-fashion.test', 'store' => $fashion, 'primary' => false],
            ['hostname' => 'acme-electronics.test', 'store' => $electronics, 'primary' => true],
        ] as $domain) {
            StoreDomain::query()->updateOrCreate(
                ['hostname' => $domain['hostname']],
                [
                    'store_id' => $domain['store']->id,
                    'type' => StoreDomainType::Storefront,
                    'is_primary' => $domain['primary'],
                    'tls_mode' => 'managed',
                ],
            );
        }
    }
}
