<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        $zones = [
            [$fashion, 'Domestic', ['DE'], [['Standard Shipping', 499], ['Express Shipping', 999]]],
            [$fashion, 'EU', ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], [['EU Standard', 899]]],
            [$fashion, 'Rest of World', ['US', 'GB', 'CA', 'AU'], [['International', 1499]]],
            [$electronics, 'Germany', ['DE'], [['Standard', 0]]],
        ];
        foreach ($zones as [$store, $name, $countries, $rates]) {
            $zone = ShippingZone::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'name' => $name], ['countries_json' => $countries, 'regions_json' => []]);
            foreach ($rates as [$rateName, $amount]) {
                $zone->rates()->updateOrCreate(['name' => $rateName], ['type' => 'flat', 'config_json' => ['amount' => $amount], 'is_active' => true]);
            }
        }
    }
}
