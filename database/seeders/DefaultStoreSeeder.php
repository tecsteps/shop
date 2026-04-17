<?php

namespace Database\Seeders;

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class DefaultStoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['billing_email' => 'billing@shop.test'],
            ['name' => 'Shop Holdings'],
        );

        $store = Store::query()->firstOrCreate(
            ['handle' => 'shop'],
            [
                'organization_id' => $organization->getKey(),
                'name' => 'Shop',
                'status' => StoreStatus::Active->value,
                'default_currency' => 'USD',
                'default_locale' => 'en',
                'timezone' => 'UTC',
            ],
        );

        StoreDomain::query()->firstOrCreate(
            ['hostname' => 'shop.test'],
            [
                'store_id' => $store->getKey(),
                'type' => StoreDomainType::Storefront->value,
                'is_primary' => 1,
                'tls_mode' => 'managed',
                'created_at' => now(),
            ],
        );

        StoreDomain::query()->firstOrCreate(
            ['hostname' => 'admin.shop.test'],
            [
                'store_id' => $store->getKey(),
                'type' => StoreDomainType::Admin->value,
                'is_primary' => 0,
                'tls_mode' => 'managed',
                'created_at' => now(),
            ],
        );

        StoreSettings::query()->updateOrCreate(
            ['store_id' => $store->getKey()],
            ['settings_json' => '{}'],
        );
    }
}
