<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        // Acme Fashion tax settings
        TaxSettings::factory()->create([
            'store_id' => $fashion->id,
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => true,
            'config_json' => ['default_rate_bps' => 1900],
        ]);

        // Acme Electronics tax settings
        TaxSettings::factory()->create([
            'store_id' => $electronics->id,
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => true,
            'config_json' => ['default_rate_bps' => 1900],
        ]);

        // Acme Fashion - Domestic zone
        $domestic = ShippingZone::factory()->create([
            'store_id' => $fashion->id,
            'name' => 'Domestic',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $domestic->id,
            'name' => 'Standard Shipping',
            'type' => 'flat',
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $domestic->id,
            'name' => 'Express Shipping',
            'type' => 'flat',
            'config_json' => ['amount' => 999],
            'is_active' => true,
        ]);

        // Acme Fashion - EU zone
        $eu = ShippingZone::factory()->create([
            'store_id' => $fashion->id,
            'name' => 'EU',
            'countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $eu->id,
            'name' => 'EU Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 899],
            'is_active' => true,
        ]);

        // Acme Fashion - Rest of World zone
        $row = ShippingZone::factory()->create([
            'store_id' => $fashion->id,
            'name' => 'Rest of World',
            'countries_json' => ['US', 'GB', 'CA', 'AU'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $row->id,
            'name' => 'International',
            'type' => 'flat',
            'config_json' => ['amount' => 1499],
            'is_active' => true,
        ]);

        // Acme Electronics - Germany zone
        $deZone = ShippingZone::factory()->create([
            'store_id' => $electronics->id,
            'name' => 'Germany',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $deZone->id,
            'name' => 'Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 0],
            'is_active' => true,
        ]);
    }
}
