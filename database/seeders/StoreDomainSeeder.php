<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class StoreDomainSeeder extends Seeder
{
    /**
     * Seed the demo store domains.
     *
     * The shop.test hostname is served locally by Laravel Herd and must
     * resolve to the demo store so the local site finds its tenant.
     */
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $domains = [
            ['store_id' => $fashion->getKey(), 'hostname' => 'acme-fashion.test', 'type' => 'storefront', 'is_primary' => true],
            ['store_id' => $fashion->getKey(), 'hostname' => 'admin.acme-fashion.test', 'type' => 'admin', 'is_primary' => false],
            ['store_id' => $fashion->getKey(), 'hostname' => 'shop.test', 'type' => 'storefront', 'is_primary' => false],
            ['store_id' => $fashion->getKey(), 'hostname' => '2026-06-09-claude-code-fable-5.agentic-engineers.dev', 'type' => 'storefront', 'is_primary' => false],
            ['store_id' => $electronics->getKey(), 'hostname' => 'acme-electronics.test', 'type' => 'storefront', 'is_primary' => true],
        ];

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appHost) && ! in_array($appHost, ['localhost', '127.0.0.1', '::1'], true)) {
            $domains[] = [
                'store_id' => $fashion->getKey(),
                'hostname' => strtolower($appHost),
                'type' => 'storefront',
                'is_primary' => false,
            ];
        }

        foreach ($domains as $domain) {
            StoreDomain::query()->updateOrCreate(
                ['hostname' => $domain['hostname']],
                [
                    'store_id' => $domain['store_id'],
                    'type' => $domain['type'],
                    'is_primary' => $domain['is_primary'],
                    'tls_mode' => 'managed',
                ],
            );

            Cache::forget('store_domain:'.$domain['hostname']);
        }
    }
}
