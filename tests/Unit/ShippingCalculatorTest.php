<?php

use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('matches a zone by country code', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $zone = ShippingZone::factory()->for($store)->create([
        'name' => 'US Zone',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'is_active' => true,
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country_code' => 'US']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard');
});

it('matches a zone by region code', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $regionZone = ShippingZone::factory()->for($store)->create([
        'name' => 'US-CA Zone',
        'countries_json' => ['US'],
        'regions_json' => ['CA'],
    ]);

    $countryZone = ShippingZone::factory()->for($store)->create([
        'name' => 'US Zone',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $regionZone->id,
        'name' => 'CA Rate',
        'is_active' => true,
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $countryZone->id,
        'name' => 'US Rate',
        'is_active' => true,
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, [
        'country_code' => 'US',
        'province_code' => 'CA',
    ]);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('CA Rate');
});

it('returns empty when no zone matches', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country_code' => 'DE']);

    expect($rates)->toBeEmpty();
});

it('calculates a flat rate', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'status' => VariantStatus::Active,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create();
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $calculator = app(ShippingCalculator::class);
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(499);
});

it('calculates a weight-based rate', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'weight_g' => 500,
        'status' => VariantStatus::Active,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 6000,
        'line_total_amount' => 6000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create();
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Weight,
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 999, 'amount' => 299],
                ['min_g' => 1000, 'max_g' => 5000, 'amount' => 599],
            ],
            'default_amount' => 999,
        ],
    ]);

    $calculator = app(ShippingCalculator::class);
    // 500g * 3 = 1500g, falls in range 1000-5000
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(599);
});

it('calculates a price-based rate', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'status' => VariantStatus::Active,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 7500,
        'line_subtotal_amount' => 7500,
        'line_total_amount' => 7500,
    ]);

    $zone = ShippingZone::factory()->for($store)->create();
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Price,
        'config_json' => [
            'ranges' => [
                ['min_amount' => 0, 'max_amount' => 4999, 'amount' => 599],
                ['min_amount' => 5000, 'max_amount' => 9999, 'amount' => 299],
                ['min_amount' => 10000, 'max_amount' => PHP_INT_MAX, 'amount' => 0],
            ],
        ],
    ]);

    $calculator = app(ShippingCalculator::class);
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(299);
});

it('returns zero shipping when no items require shipping', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'weight_g' => 0,
        'requires_shipping' => false,
        'status' => VariantStatus::Active,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create();
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Weight,
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 0, 'amount' => 0],
                ['min_g' => 1, 'max_g' => 5000, 'amount' => 599],
            ],
            'default_amount' => 0,
        ],
    ]);

    $calculator = app(ShippingCalculator::class);
    $cost = $calculator->calculate($rate, $cart);

    // Total weight is 0 (0 * 1), falls in range 0-0
    expect($cost)->toBe(0);
});

it('returns correct rates when multiple zones match', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $zone1 = ShippingZone::factory()->for($store)->create([
        'name' => 'US Zone 1',
        'countries_json' => ['US'],
    ]);

    $zone2 = ShippingZone::factory()->for($store)->create([
        'name' => 'US Zone 2',
        'countries_json' => ['US'],
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone1->id,
        'name' => 'Rate 1',
        'is_active' => true,
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone2->id,
        'name' => 'Rate 2',
        'is_active' => true,
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country_code' => 'US']);

    expect($rates)->toHaveCount(2);
});

it('skips inactive rates', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Active Rate',
        'is_active' => true,
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Inactive Rate',
        'is_active' => false,
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country_code' => 'US']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Active Rate');
});
