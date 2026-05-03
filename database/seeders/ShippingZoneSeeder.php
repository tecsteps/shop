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
            ShippingZone::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'name' => 'DACH',
                ],
                [
                    'countries_json' => ['DE', 'AT', 'CH'],
                    'regions_json' => [],
                ],
            );
        });
    }
}
