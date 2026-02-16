<?php

namespace Database\Seeders;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFashion();
        $this->seedElectronics();
    }

    private function seedFashion(): void
    {
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        $domestic = ShippingZone::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Domestic'],
            [
                'countries_json' => ['DE'],
                'regions_json' => [],
                'is_active' => true,
            ]
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $domestic->id, 'name' => 'Standard Shipping'],
            [
                'type' => ShippingRateType::Flat,
                'amount' => 499,
                'config_json' => ['amount' => 499],
                'is_active' => true,
            ]
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $domestic->id, 'name' => 'Express Shipping'],
            [
                'type' => ShippingRateType::Flat,
                'amount' => 999,
                'config_json' => ['amount' => 999],
                'is_active' => true,
            ]
        );

        $eu = ShippingZone::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'EU'],
            [
                'countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
                'regions_json' => [],
                'is_active' => true,
            ]
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $eu->id, 'name' => 'EU Standard'],
            [
                'type' => ShippingRateType::Flat,
                'amount' => 899,
                'config_json' => ['amount' => 899],
                'is_active' => true,
            ]
        );

        $row = ShippingZone::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Rest of World'],
            [
                'countries_json' => ['US', 'GB', 'CA', 'AU'],
                'regions_json' => [],
                'is_active' => true,
            ]
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $row->id, 'name' => 'International'],
            [
                'type' => ShippingRateType::Flat,
                'amount' => 1499,
                'config_json' => ['amount' => 1499],
                'is_active' => true,
            ]
        );
    }

    private function seedElectronics(): void
    {
        $store = Store::where('slug', 'acme-electronics')->firstOrFail();

        $zone = ShippingZone::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Germany'],
            [
                'countries_json' => ['DE'],
                'regions_json' => [],
                'is_active' => true,
            ]
        );

        ShippingRate::firstOrCreate(
            ['zone_id' => $zone->id, 'name' => 'Standard'],
            [
                'type' => ShippingRateType::Flat,
                'amount' => 0,
                'config_json' => ['amount' => 0],
                'is_active' => true,
            ]
        );
    }
}
