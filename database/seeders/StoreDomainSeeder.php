<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreDomainSeeder extends Seeder
{
    /**
     * Seed the store_domains table.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            StoreDomain::firstOrCreate(
                ['hostname' => 'acme-fashion.test'],
                [
                    'store_id' => $fashion->id,
                    'type' => 'storefront',
                    'is_primary' => true,
                    'tls_mode' => 'managed',
                ],
            );

            StoreDomain::firstOrCreate(
                ['hostname' => 'admin.acme-fashion.test'],
                [
                    'store_id' => $fashion->id,
                    'type' => 'admin',
                    'is_primary' => false,
                    'tls_mode' => 'managed',
                ],
            );

            StoreDomain::firstOrCreate(
                ['hostname' => 'acme-electronics.test'],
                [
                    'store_id' => $electronics->id,
                    'type' => 'storefront',
                    'is_primary' => true,
                    'tls_mode' => 'managed',
                ],
            );
        });
    }
}
