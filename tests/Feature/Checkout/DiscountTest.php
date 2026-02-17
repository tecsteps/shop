<?php

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies percent discount in checkout pricing', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'TEN',
        'value_amount' => 10,
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
        'discount_code' => 'TEN',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(500);
});

it('applies fixed discount in checkout pricing', function () {
    $store = Store::factory()->create();
    Discount::factory()->fixed(1000)->create([
        'store_id' => $store->id,
        'code' => 'FLAT10',
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
        'discount_code' => 'FLAT10',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(1000)
        ->and($result->total)->toBe(4000);
});

it('validates discount during checkout flow', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'VALID',
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'line_subtotal_amount' => 5000,
    ]);

    $service = app(DiscountService::class);
    $discount = $service->validate('VALID', $store, $cart->load('lines'));

    expect($discount)->not->toBeNull()
        ->and($discount->code)->toBe('VALID');
});

it('rejects expired discount during pricing', function () {
    $store = Store::factory()->create();
    Discount::factory()->expired()->create([
        'store_id' => $store->id,
        'code' => 'EXPIRED',
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'line_subtotal_amount' => 5000,
    ]);

    // Pricing engine should gracefully handle expired discount
    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'discount_code' => 'EXPIRED',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(0);
});

it('free shipping discount overrides shipping cost', function () {
    $store = Store::factory()->create();
    Discount::factory()->freeShipping()->create([
        'store_id' => $store->id,
        'code' => 'FREESHIP',
    ]);

    $zone = \App\Models\ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = \App\Models\ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 999],
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
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(0)
        ->and($result->total)->toBe(3000);
});

it('rejects discount not found in validation', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $service = app(DiscountService::class);
    $service->validate('NONEXISTENT', $store, $cart);
})->throws(InvalidDiscountException::class);
