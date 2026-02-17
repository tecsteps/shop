<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes shipping in pricing result', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 3000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 3000,
        'line_subtotal_amount' => 3000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(499)
        ->and($result->total)->toBe(3499);
});

it('returns zero shipping when no rate is selected', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 3000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 3000,
        'line_subtotal_amount' => 3000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(0);
});

it('returns empty rates for unserviceable address', function () {
    $store = Store::factory()->create();
    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country' => 'JP']);

    expect($rates)->toBeEmpty();
});

it('returns all active rates for a matched zone', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Standard']);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Express']);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($store, ['country' => 'DE']);

    expect($rates)->toHaveCount(2);
});

it('calculates weight-based shipping for checkout', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->weightBased()->create(['zone_id' => $zone->id]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'price_amount' => 3000,
        'weight_g' => 400,
        'requires_shipping' => true,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 3000,
        'line_subtotal_amount' => 6000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    // 400g * 2 = 800g, within range 0-1000, amount=500
    expect($result->shipping)->toBe(500);
});
