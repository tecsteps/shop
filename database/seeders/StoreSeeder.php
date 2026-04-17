<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['billing_email' => 'billing@acme.test'],
            ['name' => 'Acme Holdings'],
        );

        $store = Store::factory()->create([
            'organization_id' => $organization->getKey(),
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store->getKey(),
            'hostname' => 'acme-fashion.test',
            'type' => StoreDomainType::Storefront->value,
            'is_primary' => 1,
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store->getKey(),
            'hostname' => 'admin.acme-fashion.test',
            'type' => StoreDomainType::Admin->value,
            'is_primary' => 0,
        ]);

        StoreSettings::query()->updateOrCreate(
            ['store_id' => $store->getKey()],
            ['settings_json' => '{}'],
        );
    }
}
