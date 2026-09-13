<?php

namespace Database\Seeders;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $this->seedZone($fashion, 'Domestic', ['DE'], [], [
            ['name' => 'Standard Shipping', 'amount' => 499],
            ['name' => 'Express Shipping', 'amount' => 999],
        ]);

        $this->seedZone($fashion, 'EU', ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], [], [
            ['name' => 'EU Standard', 'amount' => 899],
        ]);

        $this->seedZone($fashion, 'Rest of World', ['US', 'GB', 'CA', 'AU'], [], [
            ['name' => 'International', 'amount' => 1499],
        ]);

        $this->seedZone($electronics, 'Germany', ['DE'], [], [
            ['name' => 'Standard', 'amount' => 0],
        ]);
    }

    /**
     * @param  array<int, string>  $countries
     * @param  array<int, string>  $regions
     * @param  array<int, array{name: string, amount: int}>  $rates
     */
    protected function seedZone(Store $store, string $name, array $countries, array $regions, array $rates): void
    {
        $zone = ShippingZone::query()->updateOrCreate(
            ['store_id' => $store->getKey(), 'name' => $name],
            ['countries_json' => $countries, 'regions_json' => $regions]
        );

        foreach ($rates as $rate) {
            ShippingRate::query()->updateOrCreate(
                ['zone_id' => $zone->getKey(), 'name' => $rate['name']],
                [
                    'type' => ShippingRateType::Flat,
                    'config_json' => ['amount' => $rate['amount']],
                    'is_active' => true,
                ]
            );
        }
    }
}
