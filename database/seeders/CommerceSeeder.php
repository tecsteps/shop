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

            $international = ShippingZone::query()
                ->withoutGlobalScopes()
                ->firstOrCreate(
                    ['store_id' => $store->getKey(), 'name' => 'International'],
                    ['countries_json' => ['CA', 'MX', 'GB', 'DE', 'FR'], 'regions_json' => []],
                );

            ShippingRate::query()->firstOrCreate(
                ['zone_id' => $international->getKey(), 'name' => 'International Standard'],
                [
                    'type' => ShippingRateType::Flat->value,
                    'config_json' => ['amount' => 2499],
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

            $discounts = [
                [
                    'code' => 'WELCOME10',
                    'value_type' => DiscountValueType::Percent->value,
                    'value_amount' => 10,
                    'starts_at' => now()->subDay(),
                    'ends_at' => null,
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'status' => DiscountStatus::Active->value,
                    'rules_json' => [],
                ],
                [
                    'code' => 'FREESHIP',
                    'value_type' => DiscountValueType::FreeShipping->value,
                    'value_amount' => 0,
                    'starts_at' => now()->subDay(),
                    'ends_at' => null,
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'status' => DiscountStatus::Active->value,
                    'rules_json' => [],
                ],
                [
                    'code' => 'SALE20',
                    'value_type' => DiscountValueType::Fixed->value,
                    'value_amount' => 2000,
                    'starts_at' => now()->subDay(),
                    'ends_at' => null,
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'status' => DiscountStatus::Active->value,
                    'rules_json' => ['minimum_subtotal' => 5000],
                ],
                [
                    'code' => 'SPRING23',
                    'value_type' => DiscountValueType::Percent->value,
                    'value_amount' => 15,
                    'starts_at' => now()->subDays(60),
                    'ends_at' => now()->subDays(30),
                    'usage_limit' => null,
                    'usage_count' => 42,
                    'status' => DiscountStatus::Expired->value,
                    'rules_json' => [],
                ],
                [
                    'code' => 'LIMITED50',
                    'value_type' => DiscountValueType::Percent->value,
                    'value_amount' => 50,
                    'starts_at' => now()->subDays(7),
                    'ends_at' => null,
                    'usage_limit' => 10,
                    'usage_count' => 10,
                    'status' => DiscountStatus::Active->value,
                    'rules_json' => [],
                ],
            ];

            foreach ($discounts as $entry) {
                Discount::query()
                    ->withoutGlobalScopes()
                    ->updateOrCreate(
                        ['store_id' => $store->getKey(), 'code' => $entry['code']],
                        [
                            'type' => DiscountType::Code->value,
                            'value_type' => $entry['value_type'],
                            'value_amount' => $entry['value_amount'],
                            'starts_at' => $entry['starts_at'],
                            'ends_at' => $entry['ends_at'],
                            'usage_limit' => $entry['usage_limit'],
                            'usage_count' => $entry['usage_count'],
                            'rules_json' => $entry['rules_json'],
                            'status' => $entry['status'],
                        ],
                    );
            }
        });
    }
}
