<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $zones = [
                [$fashion->id, 'Domestic', ['DE'], [
                    ['Standard Shipping', 499],
                    ['Express Shipping', 999],
                ]],
                [$fashion->id, 'EU', ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], [['EU Standard', 899]]],
                [$fashion->id, 'Rest of World', ['US', 'GB', 'CA', 'AU'], [['International', 1499]]],
                [$electronics->id, 'Germany', ['DE'], [['Standard', 0]]],
            ];

            foreach ($zones as [$storeId, $name, $countries, $rates]) {
                $zone = ShippingZone::query()->updateOrCreate(
                    ['store_id' => $storeId, 'name' => $name],
                    ['countries_json' => $countries, 'regions_json' => []],
                );

                foreach ($rates as [$rateName, $amount]) {
                    ShippingRate::query()->updateOrCreate(
                        ['zone_id' => $zone->id, 'name' => $rateName],
                        ['type' => 'flat', 'config_json' => ['amount' => $amount], 'is_active' => true],
                    );
                }
            }
        });
    }
}
