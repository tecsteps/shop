<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['billing_email' => 'demo@shop.test'],
            ['name' => 'Demo Org']
        );

        $store = Store::query()->firstOrCreate(
            ['handle' => 'demo'],
            [
                'organization_id' => $organization->id,
                'name' => 'Demo Store',
                'status' => 'active',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ]
        );

        app()->instance('current_store', $store);

        StoreDomain::query()->firstOrCreate(
            ['hostname' => 'shop.test'],
            [
                'store_id' => $store->id,
                'type' => 'storefront',
                'is_primary' => true,
                'tls_mode' => 'managed',
                'created_at' => now(),
            ]
        );

        $admin = User::query()->where('email', 'admin@shop.test')->first();
        if ($admin !== null) {
            $exists = DB::table('store_users')
                ->where('store_id', $store->id)
                ->where('user_id', $admin->id)
                ->exists();

            if (! $exists) {
                DB::table('store_users')->insert([
                    'store_id' => $store->id,
                    'user_id' => $admin->id,
                    'role' => 'owner',
                    'created_at' => now(),
                ]);
            }
        }

        StoreSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            ['settings_json' => [
                'support_email' => 'support@shop.test',
                'brand_color' => '#18181b',
            ]]
        );

        $theme = Theme::query()->firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Default Theme'],
            [
                'version' => '1.0.0',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        ThemeSettings::query()->updateOrCreate(
            ['theme_id' => $theme->id],
            ['settings_json' => [
                'primary_color' => '#18181b',
                'accent_color' => '#059669',
                'font_family' => 'Inter',
            ]]
        );

        $zone = ShippingZone::query()->firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Europe'],
            [
                'countries_json' => ['DE', 'AT', 'CH'],
                'regions_json' => [],
            ]
        );

        ShippingRate::query()->firstOrCreate(
            ['zone_id' => $zone->id, 'name' => 'Standard'],
            [
                'type' => 'flat',
                'config_json' => ['amount' => 599],
                'is_active' => true,
            ]
        );

        TaxSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => 'manual',
                'prices_include_tax' => true,
                'config_json' => [
                    'name' => 'VAT',
                    'rate_basis_points' => 1900,
                ],
            ]
        );
    }
}
