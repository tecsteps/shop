<?php

use App\Enums\CartStatus;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Tests\Support\CommerceTestHelper;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('matches a zone by country code', function () {
    ['store' => $store] = test()->createStoreContext();

    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE', 'AT', 'CH'],
        'regions_json' => [],
    ]);

    $zone = app(ShippingCalculator::class)->getMatchingZone($store, ['country_code' => 'DE']);

    expect($zone)->not->toBeNull();
    expect($zone->countries_json)->toBe(['DE', 'AT', 'CH']);
});

it('matches a zone by region code', function () {
    ['store' => $store] = test()->createStoreContext();

    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'US East',
        'countries_json' => ['US'],
        'regions_json' => ['US-NY', 'US-CA'],
    ]);
    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'US Wide',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    $zone = app(ShippingCalculator::class)->getMatchingZone($store, [
        'country_code' => 'US',
        'province_code' => 'US-NY',
    ]);

    expect($zone)->not->toBeNull();
    expect($zone->name)->toBe('US East');
});

it('returns empty when no zone matches the address', function () {
    ['store' => $store] = test()->createStoreContext();

    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $zone = app(ShippingCalculator::class)->getMatchingZone($store, ['country_code' => 'FR']);

    expect($zone)->toBeNull();
});

it('calculates a flat rate', function () {
    ['store' => $store] = test()->createStoreContext();

    $variant = CommerceTestHelper::purchasableVariant($store);
    $cart = Cart::factory()->create(['store_id' => $store->id, 'status' => CartStatus::Active]);
    $cart->cartLines()->create([
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $rate = ShippingRate::factory()->create([
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart->refresh()))->toBe(499);
});

it('calculates a weight-based rate', function () {
    ['store' => $store] = test()->createStoreContext();

    $variant = CommerceTestHelper::purchasableVariant($store, ['weight_g' => 250]);

    $cart = Cart::factory()->create(['store_id' => $store->id, 'status' => CartStatus::Active]);
    $cart->cartLines()->create([
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 7500,
        'line_discount_amount' => 0,
        'line_total_amount' => 7500,
    ]);

    $rate = ShippingRate::factory()->create([
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [
            ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
            ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
        ]],
    ]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart->refresh()))->toBe(899);
});

it('calculates a price-based rate', function () {
    ['store' => $store] = test()->createStoreContext();

    $variant = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $store->id, 'status' => CartStatus::Active]);
    $cart->cartLines()->create([
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 7500,
        'line_discount_amount' => 0,
        'line_total_amount' => 7500,
    ]);

    $rate = ShippingRate::factory()->create([
        'type' => ShippingRateType::Price,
        'config_json' => ['ranges' => [
            ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
            ['min_amount' => 5001, 'amount' => 399],
        ]],
    ]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart->refresh()))->toBe(399);
});

it('returns zero shipping when no items require shipping', function () {
    ['store' => $store] = test()->createStoreContext();

    $variant = CommerceTestHelper::purchasableVariant($store, ['requires_shipping' => false]);

    $cart = Cart::factory()->create(['store_id' => $store->id, 'status' => CartStatus::Active]);
    $cart->cartLines()->create([
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'DE'], $cart->refresh());

    expect($rates)->toBeEmpty();
});

it('prefers the most specific zone when multiple zones match', function () {
    ['store' => $store] = test()->createStoreContext();

    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'Country wide',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $specific = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'Berlin',
        'countries_json' => ['DE'],
        'regions_json' => ['BE'],
    ]);

    $zone = app(ShippingCalculator::class)->getMatchingZone($store, [
        'country_code' => 'DE',
        'province_code' => 'BE',
    ]);

    expect($zone->id)->toBe($specific->id);
});

it('skips inactive rates', function () {
    ['store' => $store] = test()->createStoreContext();

    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = Cart::factory()->create(['store_id' => $store->id, 'status' => CartStatus::Active]);
    $cart->cartLines()->create([
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => false,
    ]);

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'DE'], $cart->refresh());

    expect($rates)->toBeEmpty();
});
