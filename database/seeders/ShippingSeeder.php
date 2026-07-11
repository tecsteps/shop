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
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $data = [
                'acme-fashion' => [
                    ['Domestic', ['DE'], [['Standard Shipping', 499], ['Express Shipping', 999]]],
                    ['EU', ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], [['EU Standard', 899]]],
                    ['Rest of World', ['US', 'GB', 'CA', 'AU'], [['International', 1499]]],
                ],
                'acme-electronics' => [
                    ['Germany', ['DE'], [['Standard', 0]]],
                ],
            ];
            foreach ($data as $handle => $zones) {
                $store = Store::query()->where('handle', $handle)->sole();
                foreach ($zones as [$name, $countries, $rates]) {
                    $zone = ShippingZone::query()->updateOrCreate(
                        ['store_id' => $store->id, 'name' => $name],
                        ['countries_json' => $countries, 'regions_json' => []],
                    );
                    foreach ($rates as [$rateName, $amount]) {
                        ShippingRate::query()->updateOrCreate(
                            ['zone_id' => $zone->id, 'name' => $rateName],
                            ['type' => 'flat', 'config_json' => ['amount' => $amount], 'is_active' => true],
                        );
                    }
                }
            }
        });
    }
}
