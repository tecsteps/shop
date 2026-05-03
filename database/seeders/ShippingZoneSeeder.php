<?php

namespace Database\Seeders;

use App\Enums\ShippingRateType;
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
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        $zone = ShippingZone::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'name' => 'Germany',
            ],
            [
                'countries_json' => ['DE'],
                'regions_json' => [],
            ],
        );

        $zone->rates()->updateOrCreate(
            ['name' => 'Standard Shipping'],
            [
                'type' => ShippingRateType::Flat,
                'config_json' => [
                    'amount' => 500,
                    'estimated_days_min' => 3,
                    'estimated_days_max' => 5,
                ],
                'is_active' => true,
            ],
        );

        $zone->rates()->updateOrCreate(
            ['name' => 'Express Shipping'],
            [
                'type' => ShippingRateType::Flat,
                'config_json' => [
                    'amount' => 1200,
                    'estimated_days_min' => 1,
                    'estimated_days_max' => 2,
                ],
                'is_active' => true,
            ],
        );
    }
}
