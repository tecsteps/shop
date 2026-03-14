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
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ]);

        $international = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'International',
            'countries_json' => ['US', 'GB', 'FR'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $international->id,
            'name' => 'International Shipping',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 999],
            'is_active' => true,
        ]);
    }
}
