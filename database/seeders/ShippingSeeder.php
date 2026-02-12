<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        // Acme Fashion - Domestic
        $domestic = ShippingZone::firstOrCreate(
            ['store_id' => $fashion->id, 'name' => 'Domestic'],
            ['countries_json' => ['DE'], 'regions_json' => []],
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $domestic->id, 'name' => 'Standard Shipping'],
            ['type' => 'flat', 'config_json' => ['amount' => 499], 'is_active' => true],
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $domestic->id, 'name' => 'Express Shipping'],
            ['type' => 'flat', 'config_json' => ['amount' => 999], 'is_active' => true],
        );

        // Acme Fashion - EU
        $eu = ShippingZone::firstOrCreate(
            ['store_id' => $fashion->id, 'name' => 'EU'],
            ['countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], 'regions_json' => []],
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $eu->id, 'name' => 'EU Standard'],
            ['type' => 'flat', 'config_json' => ['amount' => 899], 'is_active' => true],
        );

        // Acme Fashion - Rest of World
        $row = ShippingZone::firstOrCreate(
            ['store_id' => $fashion->id, 'name' => 'Rest of World'],
            ['countries_json' => ['US', 'GB', 'CA', 'AU'], 'regions_json' => []],
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $row->id, 'name' => 'International'],
            ['type' => 'flat', 'config_json' => ['amount' => 1499], 'is_active' => true],
        );

        // Acme Electronics - Germany (free shipping)
        $elecDomestic = ShippingZone::firstOrCreate(
            ['store_id' => $electronics->id, 'name' => 'Germany'],
            ['countries_json' => ['DE'], 'regions_json' => []],
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $elecDomestic->id, 'name' => 'Standard'],
            ['type' => 'flat', 'config_json' => ['amount' => 0], 'is_active' => true],
        );
    }
}
