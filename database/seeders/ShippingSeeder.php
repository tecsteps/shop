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
        $this->seedFashionShipping();
        $this->seedElectronicsShipping();
    }

    private function seedFashionShipping(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $domestic = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'Domestic',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $domestic->id,
            'name' => 'Standard Shipping',
            'type' => 'flat',
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ]);

        ShippingRate::create([
            'zone_id' => $domestic->id,
            'name' => 'Express Shipping',
            'type' => 'flat',
            'config_json' => ['amount' => 999],
            'is_active' => true,
        ]);

        $eu = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'EU',
            'countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $eu->id,
            'name' => 'EU Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 899],
            'is_active' => true,
        ]);

        $row = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'Rest of World',
            'countries_json' => ['US', 'GB', 'CA', 'AU'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $row->id,
            'name' => 'International',
            'type' => 'flat',
            'config_json' => ['amount' => 1499],
            'is_active' => true,
        ]);
    }

    private function seedElectronicsShipping(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();

        $zone = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'Germany',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $zone->id,
            'name' => 'Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 0],
            'is_active' => true,
        ]);
    }
}
