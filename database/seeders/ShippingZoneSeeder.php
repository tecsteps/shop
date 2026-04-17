<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        // Acme Fashion zones
        $domestic = ShippingZone::factory()->create([
            'store_id' => $fashion->id,
            'name' => 'Domestic',
            'countries_json' => ['DE'],
            'regions_json' => [],
            'is_active' => true,
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

        $eu = ShippingZone::factory()->create([
            'store_id' => $fashion->id,
            'name' => 'EU',
            'countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
            'regions_json' => [],
            'is_active' => true,
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $eu->id,
            'name' => 'EU Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 899],
            'is_active' => true,
        ]);

        $row = ShippingZone::factory()->create([
            'store_id' => $fashion->id,
            'name' => 'Rest of World',
            'countries_json' => ['US', 'GB', 'CA', 'AU'],
            'regions_json' => [],
            'is_active' => true,
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $row->id,
            'name' => 'International',
            'type' => 'flat',
            'config_json' => ['amount' => 1499],
            'is_active' => true,
        ]);

        // Acme Electronics - one zone, free shipping
        $deZone = ShippingZone::factory()->create([
            'store_id' => $electronics->id,
            'name' => 'Germany',
            'countries_json' => ['DE'],
            'regions_json' => [],
            'is_active' => true,
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
