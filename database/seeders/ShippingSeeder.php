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
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');
        $electronics = Store::query()->firstWhere('handle', 'acme-electronics');

        if ($fashion) {
            $domestic = $this->upsertZone($fashion->id, 'Domestic', ['DE'], []);
            $eu = $this->upsertZone($fashion->id, 'EU', ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'], []);
            $row = $this->upsertZone($fashion->id, 'Rest of World', ['US', 'GB', 'CA', 'AU'], []);

            $this->upsertFlatRate($domestic->id, 'Standard Shipping', 499);
            $this->upsertFlatRate($domestic->id, 'Express Shipping', 999);
            $this->upsertFlatRate($eu->id, 'EU Standard', 899);
            $this->upsertFlatRate($row->id, 'International', 1499);
        }

        if ($electronics) {
            $germany = $this->upsertZone($electronics->id, 'Germany', ['DE'], []);
            $this->upsertFlatRate($germany->id, 'Standard', 0);
        }
    }

    private function upsertZone(int $storeId, string $name, array $countries, array $regions): ShippingZone
    {
        return ShippingZone::query()->updateOrCreate(
            ['store_id' => $storeId, 'name' => $name],
            [
                'countries_json' => $countries,
                'regions_json' => $regions,
            ]
        );
    }

    private function upsertFlatRate(int $zoneId, string $name, int $amount): void
    {
        ShippingRate::query()->updateOrCreate(
            ['zone_id' => $zoneId, 'name' => $name],
            [
                'type' => 'flat',
                'config_json' => ['amount' => $amount],
                'is_active' => true,
            ]
        );
    }
}
