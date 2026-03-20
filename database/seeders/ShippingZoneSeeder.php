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
        $store = Store::first();

        if (! $store) {
            return;
        }

        $domestic = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'Domestic (DE)',
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

        $international = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'International',
            'countries_json' => ['US', 'GB', 'FR', 'AT', 'CH'],
            'regions_json' => [],
            'is_active' => true,
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $international->id,
            'name' => 'International Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 1299],
            'is_active' => true,
        ]);
    }
}
