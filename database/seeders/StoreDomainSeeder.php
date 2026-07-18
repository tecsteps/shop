<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            foreach ([
                ['store_id' => $fashion->id, 'hostname' => 'acme-fashion.test', 'type' => 'storefront', 'is_primary' => true],
                ['store_id' => $fashion->id, 'hostname' => 'admin.acme-fashion.test', 'type' => 'admin', 'is_primary' => false],
                ['store_id' => $electronics->id, 'hostname' => 'acme-electronics.test', 'type' => 'storefront', 'is_primary' => true],
            ] as $domain) {
                StoreDomain::query()->updateOrCreate(
                    ['hostname' => $domain['hostname']],
                    [...$domain, 'tls_mode' => 'managed'],
                );
            }
        });
    }
}
