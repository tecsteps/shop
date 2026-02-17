<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates full pricing with discount, shipping, and tax', function () {
    $store = Store::factory()->create();
    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1900],
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SAVE10',
        'value_amount' => 10,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 10000,
        'line_subtotal_amount' => 10000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'discount_code' => 'SAVE10',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    // subtotal=10000, discount=1000, discounted=9000, shipping=499, tax(19% of 9000)=1710
    // total=9000+499+1710=11209
    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1710)
        ->and($result->total)->toBe(11209);
});

it('handles zero total when discount equals subtotal', function () {
    $store = Store::factory()->create();
    Discount::factory()->fixed(5000)->create([
        'store_id' => $store->id,
        'code' => 'ALLFREE',
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'discount_code' => 'ALLFREE',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(5000)
        ->and($result->total)->toBe(0);
});

it('stores pricing result in checkout totals_json', function () {
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
    $checkout->update(['totals_json' => $result->toArray()]);

    $stored = $checkout->fresh()->totals_json;

    expect($stored)->toHaveKeys(['subtotal', 'total', 'currency'])
        ->and($stored['subtotal'])->toBe(3000);
});

it('calculates multiple line items correctly', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $v1 = ProductVariant::factory()->create(['price_amount' => 1000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $v1->id,
        'quantity' => 3,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 3000,
    ]);

    $v2 = ProductVariant::factory()->create(['price_amount' => 2500]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $v2->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(8000);
});

it('recalculates after shipping method change', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    $standardRate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 500],
    ]);
    $expressRate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 1500],
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
    ]);

    // Calculate with standard rate
    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $standardRate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result1 = app(PricingEngine::class)->calculate($checkout);

    // Calculate with express rate
    $checkout->update(['shipping_method_id' => $expressRate->id]);
    $result2 = app(PricingEngine::class)->calculate($checkout->fresh());

    expect($result1->shipping)->toBe(500)
        ->and($result2->shipping)->toBe(1500)
        ->and($result2->total)->toBeGreaterThan($result1->total);
});
