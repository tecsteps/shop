<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingSeeder extends Seeder
{
    /**
     * Create shipping zones and rates for each store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedZones('acme-fashion', [
                [
                    'name' => 'Domestic',
                    'countries' => ['DE'],
                    'rates' => [
                        ['name' => 'Standard Shipping', 'type' => 'flat', 'config' => ['amount' => 499]],
                        ['name' => 'Express Shipping', 'type' => 'flat', 'config' => ['amount' => 999]],
                    ],
                ],
                [
                    'name' => 'EU',
                    'countries' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
                    'rates' => [
                        ['name' => 'EU Standard', 'type' => 'flat', 'config' => ['amount' => 899]],
                    ],
                ],
                [
                    'name' => 'Rest of World',
                    'countries' => ['US', 'GB', 'CA', 'AU'],
                    'rates' => [
                        ['name' => 'International', 'type' => 'flat', 'config' => ['amount' => 1499]],
                    ],
                ],
            ]);

            $this->seedZones('acme-electronics', [
                [
                    'name' => 'Germany',
                    'countries' => ['DE'],
                    'rates' => [
                        ['name' => 'Standard', 'type' => 'flat', 'config' => ['amount' => 0]],
                    ],
                ],
            ]);
        });
    }

    /**
     * @param  array<int, array{name: string, countries: array<int, string>, rates: array<int, array<string, mixed>>}>  $zones
     */
    private function seedZones(string $storeHandle, array $zones): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        foreach ($zones as $zone) {
            $shippingZone = ShippingZone::updateOrCreate(
                ['store_id' => $store->id, 'name' => $zone['name']],
                [
                    'countries_json' => $zone['countries'],
                    'regions_json' => [],
                ],
            );

            foreach ($zone['rates'] as $rate) {
                ShippingRate::updateOrCreate(
                    ['zone_id' => $shippingZone->id, 'name' => $rate['name']],
                    [
                        'type' => $rate['type'],
                        'config_json' => $rate['config'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
