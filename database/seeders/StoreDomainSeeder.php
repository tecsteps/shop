<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreDomainSeeder extends Seeder
{
    /**
     * Attach storefront/admin hostnames to each store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedDomains('acme-fashion', [
                ['hostname' => 'acme-fashion.test', 'type' => 'storefront', 'is_primary' => true],
                ['hostname' => 'admin.acme-fashion.test', 'type' => 'admin', 'is_primary' => false],
            ]);

            $this->seedDomains('acme-electronics', [
                ['hostname' => 'acme-electronics.test', 'type' => 'storefront', 'is_primary' => true],
            ]);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $domains
     */
    private function seedDomains(string $storeHandle, array $domains): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        foreach ($domains as $domain) {
            StoreDomain::updateOrCreate(
                ['hostname' => $domain['hostname']],
                [
                    'store_id' => $store->id,
                    'type' => $domain['type'],
                    'is_primary' => $domain['is_primary'],
                    'tls_mode' => 'managed',
                ],
            );
        }
    }
}
