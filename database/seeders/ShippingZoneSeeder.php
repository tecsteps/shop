<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->get()->each(function (Store $store): void {
            ShippingZone::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->delete();

            ShippingZone::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'name' => 'Domestic',
                ],
                [
                    'countries_json' => ['DE'],
                    'regions_json' => [],
                ],
            );

            ShippingZone::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'name' => 'International',
                ],
                [
                    'countries_json' => ['AT', 'CH', 'US', 'GB', 'CA', 'AU'],
                    'regions_json' => [],
                ],
            );
        });
    }
}
