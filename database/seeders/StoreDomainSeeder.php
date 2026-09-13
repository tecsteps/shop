<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Enums\TlsMode;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $this->seedDomain($fashion, 'acme-fashion.test', StoreDomainType::Storefront, true);
        $this->seedDomain($fashion, 'admin.acme-fashion.test', StoreDomainType::Admin, false);
        $this->seedDomain($electronics, 'acme-electronics.test', StoreDomainType::Storefront, true);
    }

    protected function seedDomain(Store $store, string $hostname, StoreDomainType $type, bool $isPrimary): void
    {
        StoreDomain::query()->updateOrCreate(
            ['hostname' => $hostname],
            [
                'store_id' => $store->getKey(),
                'type' => $type,
                'is_primary' => $isPrimary,
                'tls_mode' => TlsMode::Managed,
            ]
        );
    }
}
