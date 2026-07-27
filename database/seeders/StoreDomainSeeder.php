<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreDomainSeeder extends Seeder
{
    /**
     * Attach the demo hostnames to the stores (spec 07 §3.3).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = $this->store('acme-fashion');
            $electronics = $this->store('acme-electronics');

            $domains = [
                [$fashion->id, 'acme-fashion.test', StoreDomainType::Storefront, true],
                [$fashion->id, 'admin.acme-fashion.test', StoreDomainType::Admin, false],
                [$electronics->id, 'acme-electronics.test', StoreDomainType::Storefront, true],
            ];

            foreach ($domains as [$storeId, $hostname, $type, $isPrimary]) {
                StoreDomain::query()->updateOrCreate(
                    ['hostname' => $hostname],
                    [
                        'store_id' => $storeId,
                        'type' => $type,
                        'is_primary' => $isPrimary,
                        'tls_mode' => 'managed',
                    ],
                );
            }
        });
    }

    /**
     * Look up a store seeded by StoreSeeder.
     */
    private function store(string $handle): Store
    {
        return Store::query()->where('handle', $handle)->firstOrFail();
    }
}
