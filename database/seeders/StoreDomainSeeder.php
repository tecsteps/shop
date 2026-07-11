<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');
            $domains = [
                ['store' => 'acme-fashion', 'hostname' => 'acme-fashion.test', 'type' => 'storefront', 'is_primary' => true],
                ['store' => 'acme-fashion', 'hostname' => 'admin.acme-fashion.test', 'type' => 'admin', 'is_primary' => false],
                ['store' => 'acme-electronics', 'hostname' => 'acme-electronics.test', 'type' => 'storefront', 'is_primary' => true],
            ];

            foreach ($domains as $domain) {
                StoreDomain::query()->updateOrCreate(
                    ['hostname' => $domain['hostname']],
                    ['store_id' => $stores[$domain['store']]->id, 'type' => $domain['type'], 'is_primary' => $domain['is_primary'], 'tls_mode' => 'managed'],
                );
            }
        });
    }
}
