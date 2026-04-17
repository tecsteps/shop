<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $org = Organization::where('billing_email', 'billing@acme.test')->firstOrFail();

            $fashion = Store::firstOrCreate(
                ['handle' => 'acme-fashion'],
                [
                    'organization_id' => $org->id,
                    'name' => 'Acme Fashion',
                    'status' => 'active',
                    'default_currency' => 'EUR',
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
            );

            $electronics = Store::firstOrCreate(
                ['handle' => 'acme-electronics'],
                [
                    'organization_id' => $org->id,
                    'name' => 'Acme Electronics',
                    'status' => 'active',
                    'default_currency' => 'EUR',
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
            );

            // Store Domains
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

            // Store Settings
            StoreSettings::updateOrCreate(
                ['store_id' => $fashion->id],
                [
                    'settings_json' => [
                        'store_name' => 'Acme Fashion',
                        'contact_email' => 'hello@acme-fashion.test',
                        'order_number_prefix' => '#',
                        'order_number_start' => 1001,
                    ],
                ],
            );

            StoreSettings::updateOrCreate(
                ['store_id' => $electronics->id],
                [
                    'settings_json' => [
                        'store_name' => 'Acme Electronics',
                        'contact_email' => 'hello@acme-electronics.test',
                        'order_number_prefix' => '#',
                        'order_number_start' => 5001,
                    ],
                ],
            );

            // Tax Settings
            TaxSettings::updateOrCreate(
                ['store_id' => $fashion->id],
                [
                    'mode' => 'manual',
                    'provider' => 'none',
                    'prices_include_tax' => true,
                    'config_json' => ['default_rate_bps' => 1900],
                ],
            );

            TaxSettings::updateOrCreate(
                ['store_id' => $electronics->id],
                [
                    'mode' => 'manual',
                    'provider' => 'none',
                    'prices_include_tax' => true,
                    'config_json' => ['default_rate_bps' => 1900],
                ],
            );
        });
    }
}
