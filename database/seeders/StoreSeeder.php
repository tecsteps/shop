<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $acme = Organization::query()->where('billing_email', 'billing@acme.test')->firstOrFail();

        // Store 1: Acme Fashion
        $fashion = Store::factory()->create([
            'organization_id' => $acme->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        StoreDomain::factory()->primary()->create([
            'store_id' => $fashion->id,
            'hostname' => 'acme-fashion.test',
            'type' => 'storefront',
        ]);

        StoreDomain::factory()->admin()->create([
            'store_id' => $fashion->id,
            'hostname' => 'admin.acme-fashion.test',
            'is_primary' => false,
        ]);

        StoreSettings::factory()->create([
            'store_id' => $fashion->id,
            'settings_json' => [
                'store_name' => 'Acme Fashion',
                'contact_email' => 'hello@acme-fashion.test',
                'order_number_prefix' => '#',
                'order_number_start' => 1001,
            ],
        ]);

        // Store 2: Acme Electronics
        $electronics = Store::factory()->create([
            'organization_id' => $acme->id,
            'name' => 'Acme Electronics',
            'handle' => 'acme-electronics',
            'status' => 'active',
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ]);

        StoreDomain::factory()->primary()->create([
            'store_id' => $electronics->id,
            'hostname' => 'acme-electronics.test',
            'type' => 'storefront',
        ]);

        StoreSettings::factory()->create([
            'store_id' => $electronics->id,
            'settings_json' => [
                'store_name' => 'Acme Electronics',
                'contact_email' => 'hello@acme-electronics.test',
                'order_number_prefix' => '#',
                'order_number_start' => 5001,
            ],
        ]);
    }
}
