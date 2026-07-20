<?php

namespace Database\Seeders;

use App\Enums\ShippingRateType;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingSeeder extends Seeder
{
    /**
     * Create the shipping zones and flat rates (spec 07 §3.8).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $this->seedZone($fashion->id, 'Domestic', ['DE'], [
                ['Standard Shipping', 499],
                ['Express Shipping', 999],
            ]);

            $this->seedZone($fashion->id, 'EU', ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], [
                ['EU Standard', 899],
            ]);

            $this->seedZone($fashion->id, 'Rest of World', ['US', 'GB', 'CA', 'AU'], [
                ['International', 1499],
            ]);

            $this->seedZone($electronics->id, 'Germany', ['DE'], [
                ['Standard', 0],
            ]);
        });
    }

    /**
     * Create one zone with its flat rates.
     *
     * @param  list<string>  $countries
     * @param  list<array{0: string, 1: int}>  $rates
     */
    private function seedZone(int $storeId, string $name, array $countries, array $rates): void
    {
        $zone = ShippingZone::query()->updateOrCreate(
            ['store_id' => $storeId, 'name' => $name],
            ['countries_json' => $countries, 'regions_json' => []],
        );

        foreach ($rates as [$rateName, $amount]) {
            $zone->rates()->updateOrCreate(
                ['name' => $rateName],
                [
                    'type' => ShippingRateType::Flat,
                    'config_json' => ['amount' => $amount],
                    'is_active' => true,
                ],
            );
        }
    }
}
