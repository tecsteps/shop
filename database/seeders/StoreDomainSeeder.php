<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');

        foreach ([
            ['store' => 'acme-fashion', 'hostname' => 'acme-fashion.test', 'type' => 'storefront', 'is_primary' => true],
            ['store' => 'acme-fashion', 'hostname' => 'admin.acme-fashion.test', 'type' => 'admin', 'is_primary' => false],
            ['store' => 'acme-electronics', 'hostname' => 'acme-electronics.test', 'type' => 'storefront', 'is_primary' => true],
        ] as $domain) {
            $store = $stores->get($domain['store']);

            $store?->domains()->updateOrCreate(
                ['hostname' => $domain['hostname']],
                ['type' => $domain['type'], 'is_primary' => $domain['is_primary'], 'tls_mode' => 'managed'],
            );
        }
    }
}
