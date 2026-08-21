<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');

        $fashionZones = [
            ['name' => 'Domestic', 'countries_json' => ['DE'], 'rates' => [['name' => 'Standard Shipping', 'amount' => 499], ['name' => 'Express Shipping', 'amount' => 999]]],
            ['name' => 'EU', 'countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], 'rates' => [['name' => 'EU Standard', 'amount' => 899]]],
            ['name' => 'Rest of World', 'countries_json' => ['US', 'GB', 'CA', 'AU'], 'rates' => [['name' => 'International', 'amount' => 1499]]],
        ];

        foreach ($fashionZones as $zoneData) {
            $zone = ShippingZone::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $stores['acme-fashion']->getKey(), 'name' => $zoneData['name']],
                ['countries_json' => $zoneData['countries_json'], 'regions_json' => []],
            );

            $this->seedRates($zone, $zoneData['rates']);
        }

        $electronicsZone = ShippingZone::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $stores['acme-electronics']->getKey(), 'name' => 'Germany'],
            ['countries_json' => ['DE'], 'regions_json' => []],
        );

        $this->seedRates($electronicsZone, [['name' => 'Standard', 'amount' => 0]]);
    }

    private function seedRates(ShippingZone $zone, array $rates): void
    {
        foreach ($rates as $rate) {
            ShippingRate::query()->updateOrCreate(
                ['shipping_zone_id' => $zone->getKey(), 'name' => $rate['name']],
                ['zone_id' => $zone->getKey(), 'type' => 'flat', 'price_amount' => $rate['amount'], 'currency' => 'EUR', 'config_json' => ['amount' => $rate['amount']], 'is_active' => true, 'estimated_days_min' => $rate['amount'] === 0 ? 0 : ($rate['amount'] >= 999 ? 1 : 3), 'estimated_days_max' => $rate['amount'] === 0 ? 0 : ($rate['amount'] >= 999 ? 2 : 5)],
            );
        }
    }
}
