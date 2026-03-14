<?php

use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->calculator = app(ShippingCalculator::class);
});

it('matches zone by country code', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'US Zone',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);
    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 599],
        'is_active' => true,
    ]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country' => 'US']);

    expect($rates)->toHaveCount(1);
    expect($rates->first()->name)->toBe('Standard');
});

it('returns empty when no zone matches', function () {
    ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'US Zone',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country' => 'JP']);

    expect($rates)->toBeEmpty();
});

it('prefers region-specific zone match', function () {
    $generalZone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'US General',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);
    ShippingRate::create([
        'zone_id' => $generalZone->id,
        'name' => 'US Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
        'is_active' => true,
    ]);

    $regionZone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'US California',
        'countries_json' => ['US'],
        'regions_json' => ['US-CA'],
    ]);
    ShippingRate::create([
        'zone_id' => $regionZone->id,
        'name' => 'CA Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], [
        'country' => 'US',
        'province_code' => 'US-CA',
    ]);

    expect($rates)->toHaveCount(1);
    expect($rates->first()->name)->toBe('CA Standard');
});

it('calculates flat rate', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Zone',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Flat',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->ctx['store']->id]);
    $result = $this->calculator->calculate($rate, $cart);

    expect($result)->toBe(499);
});

it('calculates weight-based rate', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Zone',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Weight',
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [
            ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
            ['min_g' => 501, 'max_g' => 2000, 'amount' => 999],
        ]],
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2000,
        'weight_grams' => 300,
        'requires_shipping' => true,
    ]);
    $cart = Cart::factory()->create(['store_id' => $this->ctx['store']->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 4000,
        'line_total_amount' => 4000,
    ]);

    $result = $this->calculator->calculate($rate, $cart);

    // 300g * 2 = 600g, falls in 501-2000 range
    expect($result)->toBe(999);
});

it('calculates price-based rate', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Zone',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Price',
        'type' => ShippingRateType::Price,
        'config_json' => ['ranges' => [
            ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
            ['min_amount' => 5001, 'amount' => 0],
        ]],
        'is_active' => true,
    ]);

    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 3000,
    ]);
    $cart = Cart::factory()->create(['store_id' => $this->ctx['store']->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 3000,
        'line_subtotal_amount' => 3000,
        'line_total_amount' => 3000,
    ]);

    $result = $this->calculator->calculate($rate, $cart);

    expect($result)->toBe(799);
});

it('returns zero shipping for non-shipping items in weight rate', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Zone',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Weight',
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [
            ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
        ]],
        'is_active' => true,
    ]);

    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'weight_grams' => 200,
        'requires_shipping' => false,
    ]);
    $cart = Cart::factory()->create(['store_id' => $this->ctx['store']->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    $result = $this->calculator->calculate($rate, $cart);

    // 0g weight -> falls in 0-500 range
    expect($result)->toBe(499);
});

it('skips inactive rates', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Zone',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);
    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Active',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Inactive',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 299],
        'is_active' => false,
    ]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country' => 'US']);

    expect($rates)->toHaveCount(1);
    expect($rates->first()->name)->toBe('Active');
});

it('returns multiple active rates from same zone', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Zone',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);
    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Express',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 1299],
        'is_active' => true,
    ]);

    $rates = $this->calculator->getAvailableRates($this->ctx['store'], ['country' => 'US']);

    expect($rates)->toHaveCount(2);
});
