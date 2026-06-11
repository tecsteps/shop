<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    /**
     * Seed demo shipping zones and rates (spec 07 section 3.8).
     */
    public function run(): void
    {
        $zonesByStore = [
            'acme-fashion' => [
                [
                    'name' => 'Domestic',
                    'countries' => ['DE'],
                    'rates' => [
                        ['name' => 'Standard Shipping', 'amount' => 499],
                        ['name' => 'Express Shipping', 'amount' => 999],
                    ],
                ],
                [
                    'name' => 'EU',
                    'countries' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
                    'rates' => [
                        ['name' => 'EU Standard', 'amount' => 899],
                    ],
                ],
                [
                    'name' => 'Rest of World',
                    'countries' => ['US', 'GB', 'CA', 'AU'],
                    'rates' => [
                        ['name' => 'International', 'amount' => 1499],
                    ],
                ],
            ],
            'acme-electronics' => [
                [
                    'name' => 'Germany',
                    'countries' => ['DE'],
                    'rates' => [
                        ['name' => 'Standard', 'amount' => 0],
                    ],
                ],
            ],
        ];

        foreach ($zonesByStore as $handle => $zones) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();

            foreach ($zones as $zoneData) {
                $zone = ShippingZone::query()->withoutGlobalScopes()->updateOrCreate(
                    ['store_id' => $store->getKey(), 'name' => $zoneData['name']],
                    ['countries_json' => $zoneData['countries'], 'regions_json' => []],
                );

                foreach ($zoneData['rates'] as $rateData) {
                    ShippingRate::query()->updateOrCreate(
                        ['zone_id' => $zone->getKey(), 'name' => $rateData['name']],
                        [
                            'type' => 'flat',
                            'config_json' => ['amount' => $rateData['amount']],
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
