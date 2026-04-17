<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $acmeFashion = Store::where('handle', 'acme-fashion')->first();
        $acmeElectronics = Store::where('handle', 'acme-electronics')->first();

        if ($acmeFashion) {
            $this->seedAcmeFashionShipping($acmeFashion);
        }

        if ($acmeElectronics) {
            $this->seedAcmeElectronicsShipping($acmeElectronics);
        }
    }

    protected function seedAcmeFashionShipping(Store $store): void
    {
        $domestic = ShippingZone::query()->create([
            'store_id' => $store->id,
            'name' => 'Domestic',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::query()->create([
            'zone_id' => $domestic->id,
            'name' => 'Standard Shipping',
            'type' => 'flat',
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ]);

        ShippingRate::query()->create([
            'zone_id' => $domestic->id,
            'name' => 'Express Shipping',
            'type' => 'flat',
            'config_json' => ['amount' => 999],
            'is_active' => true,
        ]);

        $eu = ShippingZone::query()->create([
            'store_id' => $store->id,
            'name' => 'EU',
            'countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
            'regions_json' => [],
        ]);

        ShippingRate::query()->create([
            'zone_id' => $eu->id,
            'name' => 'EU Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 899],
            'is_active' => true,
        ]);

        $row = ShippingZone::query()->create([
            'store_id' => $store->id,
            'name' => 'Rest of World',
            'countries_json' => ['US', 'GB', 'CA', 'AU'],
            'regions_json' => [],
        ]);

        ShippingRate::query()->create([
            'zone_id' => $row->id,
            'name' => 'International',
            'type' => 'flat',
            'config_json' => ['amount' => 1499],
            'is_active' => true,
        ]);
    }

    protected function seedAcmeElectronicsShipping(Store $store): void
    {
        $germany = ShippingZone::query()->create([
            'store_id' => $store->id,
            'name' => 'Germany',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::query()->create([
            'zone_id' => $germany->id,
            'name' => 'Standard',
            'type' => 'flat',
            'config_json' => ['amount' => 0],
            'is_active' => true,
        ]);
    }
}
