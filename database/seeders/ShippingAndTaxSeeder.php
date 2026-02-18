<?php

namespace Database\Seeders;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use Illuminate\Database\Seeder;

class ShippingAndTaxSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->first();

        if (! $store) {
            return;
        }

        $this->seedShippingZones($store);
        $this->seedTaxSettings($store);
        $this->seedDiscounts($store);
    }

    private function seedShippingZones(Store $store): void
    {
        $domestic = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'Germany',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $domestic->id,
            'name' => 'Standard Shipping',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 499],
        ]);

        ShippingRate::factory()->weightBased()->create([
            'zone_id' => $domestic->id,
            'name' => 'Weight-based Shipping',
        ]);

        $europe = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'Europe',
            'countries_json' => ['AT', 'CH', 'FR', 'NL', 'BE', 'IT', 'ES'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $europe->id,
            'name' => 'European Standard',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 999],
        ]);
    }

    private function seedTaxSettings(Store $store): void
    {
        TaxSettings::factory()->create([
            'store_id' => $store->id,
            'mode' => TaxMode::Manual,
            'rate_percent' => 1900,
            'prices_include_tax' => true,
            'tax_shipping' => false,
        ]);
    }

    private function seedDiscounts(Store $store): void
    {
        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'WELCOME10',
            'title' => '10% Welcome Discount',
            'type' => DiscountType::Code,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'status' => DiscountStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
        ]);

        Discount::factory()->fixed(500)->create([
            'store_id' => $store->id,
            'code' => '5OFF',
            'title' => '5 EUR Off',
            'status' => DiscountStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
        ]);

        Discount::factory()->freeShipping()->create([
            'store_id' => $store->id,
            'code' => 'FREESHIP',
            'title' => 'Free Shipping',
            'status' => DiscountStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
        ]);
    }
}
