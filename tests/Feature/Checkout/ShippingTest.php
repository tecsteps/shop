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

function createShippingTestContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
    ]);
}

it('returns available shipping rates for address', function () {
    $ctx = createShippingTestContext();
    $store = $ctx['store'];

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Express',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country_code' => 'US']);

    expect($rates)->toHaveCount(2);
});

it('returns empty when no zone matches address', function () {
    $ctx = createShippingTestContext();
    $store = $ctx['store'];

    ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country_code' => 'JP']);

    expect($rates)->toBeEmpty();
});

it('calculates flat rate correctly', function () {
    $ctx = createShippingTestContext();
    $cart = $ctx['cart'];

    $zone = ShippingZone::factory()->for($ctx['store'])->create();
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $calculator = app(ShippingCalculator::class);
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    $ctx = createShippingTestContext();
    $cart = $ctx['cart'];

    $zone = ShippingZone::factory()->for($ctx['store'])->create();
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Weight,
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 299],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 599],
            ],
            'default_amount' => 999,
        ],
    ]);

    $calculator = app(ShippingCalculator::class);
    // variant weight_g = 500, quantity = 2, total = 1000g
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(599);
});

it('returns zero shipping when all items are digital', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
        'weight_g' => 0,
        'requires_shipping' => false,
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

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

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

    expect($cost)->toBe(0);
});
