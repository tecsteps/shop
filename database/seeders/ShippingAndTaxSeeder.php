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
        $this->seedAcmeFashion();
        $this->seedAcmeElectronics();
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::where('handle', 'acme-fashion')->first();
        if (! $store) {
            return;
        }

        // Shipping Zone 1: Domestic
        $domestic = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'Domestic',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $domestic->id,
            'name' => 'Standard Shipping',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 499],
            'is_active' => true,
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $domestic->id,
            'name' => 'Express Shipping',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 999],
            'is_active' => true,
        ]);

        // Shipping Zone 2: EU
        $eu = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'EU',
            'countries_json' => ['AT', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $eu->id,
            'name' => 'EU Standard',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 899],
            'is_active' => true,
        ]);

        // Shipping Zone 3: Rest of World
        $row = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'Rest of World',
            'countries_json' => ['US', 'GB', 'CA', 'AU'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $row->id,
            'name' => 'International',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 1499],
            'is_active' => true,
        ]);

        // Tax settings
        TaxSettings::factory()->create([
            'store_id' => $store->id,
            'mode' => TaxMode::Manual,
            'rate_percent' => 1900,
            'prices_include_tax' => true,
            'tax_shipping' => false,
        ]);

        // Discounts
        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'WELCOME10',
            'title' => '10% Welcome Discount',
            'type' => DiscountType::Code,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'status' => DiscountStatus::Active,
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => null,
            'usage_count' => 3,
            'minimum_purchase_amount' => 2000,
            'rules_json' => ['minimum_purchase_amount' => 2000],
        ]);

        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'FLAT5',
            'title' => '5 EUR Off',
            'type' => DiscountType::Code,
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => 500,
            'status' => DiscountStatus::Active,
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => null,
            'usage_count' => 0,
        ]);

        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'FREESHIP',
            'title' => 'Free Shipping',
            'type' => DiscountType::Code,
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
            'status' => DiscountStatus::Active,
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => null,
            'usage_count' => 1,
        ]);

        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'EXPIRED20',
            'title' => '20% Expired Discount',
            'type' => DiscountType::Code,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 20,
            'status' => DiscountStatus::Expired,
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-12-31',
            'usage_limit' => null,
            'usage_count' => 0,
        ]);

        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'MAXED',
            'title' => '10% Maxed Out',
            'type' => DiscountType::Code,
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'status' => DiscountStatus::Active,
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::where('handle', 'acme-electronics')->first();
        if (! $store) {
            return;
        }

        $domestic = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'name' => 'Germany',
            'countries_json' => ['DE'],
            'regions_json' => [],
        ]);

        ShippingRate::factory()->create([
            'zone_id' => $domestic->id,
            'name' => 'Standard',
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => 0],
            'is_active' => true,
        ]);

        TaxSettings::factory()->create([
            'store_id' => $store->id,
            'mode' => TaxMode::Manual,
            'rate_percent' => 1900,
            'prices_include_tax' => true,
            'tax_shipping' => false,
        ]);
    }
}
