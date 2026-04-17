<?php

namespace Database\Seeders;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\TaxProviderType;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()->get()->each(function (Store $store): void {
            $zone = ShippingZone::query()
                ->withoutGlobalScopes()
                ->firstOrCreate(
                    ['store_id' => $store->getKey(), 'name' => 'Domestic'],
                    ['countries_json' => ['US'], 'regions_json' => []],
                );

            ShippingRate::query()->firstOrCreate(
                ['zone_id' => $zone->getKey(), 'name' => 'Standard'],
                [
                    'type' => ShippingRateType::Flat->value,
                    'config_json' => ['amount' => 799],
                    'is_active' => 1,
                ],
            );

            ShippingRate::query()->firstOrCreate(
                ['zone_id' => $zone->getKey(), 'name' => 'Express'],
                [
                    'type' => ShippingRateType::Flat->value,
                    'config_json' => ['amount' => 1499],
                    'is_active' => 1,
                ],
            );

            TaxSettings::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                [
                    'mode' => TaxMode::Manual->value,
                    'provider' => TaxProviderType::None->value,
                    'prices_include_tax' => 0,
                    'config_json' => ['default_rate_bps' => 0],
                ],
            );

            Discount::query()
                ->withoutGlobalScopes()
                ->firstOrCreate(
                    ['store_id' => $store->getKey(), 'code' => 'WELCOME10'],
                    [
                        'type' => DiscountType::Code->value,
                        'value_type' => DiscountValueType::Percent->value,
                        'value_amount' => 10,
                        'starts_at' => now()->subDay(),
                        'ends_at' => null,
                        'usage_limit' => null,
                        'usage_count' => 0,
                        'rules_json' => [],
                        'status' => DiscountStatus::Active->value,
                    ],
                );
        });
    }
}
