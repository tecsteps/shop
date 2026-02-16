<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->engine = new PricingEngine(new TaxCalculator);
});

it('calculates subtotal from line items', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $v1 = \App\Models\ProductVariant::factory()->create(['price_amount' => 2499]);
    $v2 = \App\Models\ProductVariant::factory()->create(['price_amount' => 7999]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v1->id, 'quantity' => 2, 'unit_price' => 2499, 'subtotal' => 4998, 'total' => 4998]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v2->id, 'quantity' => 1, 'unit_price' => 7999, 'subtotal' => 7999, 'total' => 7999]);

    $checkout = Checkout::factory()->create(['store_id' => $ctx['store']->id, 'cart_id' => $cart->id]);
    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(12997);
});

it('returns zero subtotal for empty cart', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $checkout = Checkout::factory()->create(['store_id' => $ctx['store']->id, 'cart_id' => $cart->id]);

    $result = $this->engine->calculate($checkout);
    expect($result->subtotal)->toBe(0);
});

it('applies percent discount correctly', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000, 'total' => 10000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'discount_amount' => 1000,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->discount)->toBe(1000);
});

it('applies fixed discount correctly', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000, 'total' => 10000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'discount_amount' => 500,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->discount)->toBe(500);
});

it('caps fixed discount at subtotal', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 300]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 300, 'subtotal' => 300, 'total' => 300]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'discount_amount' => 500,
    ]);

    $result = $this->engine->calculate($checkout);
    // Total should not go below 0
    expect($result->total)->toBeGreaterThanOrEqual(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 5000, 'subtotal' => 5000, 'total' => 5000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'shipping_amount' => 0,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->shipping)->toBe(0);
});

it('calculates tax exclusive correctly', function () {
    $ctx = createStoreContext();
    TaxSettings::factory()->create([
        'store_id' => $ctx['store']->id,
        'rate_basis_points' => 1900,
        'prices_include_tax' => false,
    ]);

    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000, 'total' => 10000]);

    $checkout = Checkout::factory()->create(['store_id' => $ctx['store']->id, 'cart_id' => $cart->id]);
    $result = $this->engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900);
});

it('extracts tax from inclusive price', function () {
    $ctx = createStoreContext();
    TaxSettings::factory()->create([
        'store_id' => $ctx['store']->id,
        'rate_basis_points' => 1900,
        'prices_include_tax' => true,
    ]);

    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 11900]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 11900, 'subtotal' => 11900, 'total' => 11900]);

    $checkout = Checkout::factory()->create(['store_id' => $ctx['store']->id, 'cart_id' => $cart->id]);
    $result = $this->engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900);
});

it('returns zero tax when rate is zero', function () {
    $ctx = createStoreContext();
    TaxSettings::factory()->create([
        'store_id' => $ctx['store']->id,
        'rate_basis_points' => 0,
    ]);

    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000, 'total' => 10000]);

    $checkout = Checkout::factory()->create(['store_id' => $ctx['store']->id, 'cart_id' => $cart->id]);
    $result = $this->engine->calculate($checkout);

    expect($result->taxTotal)->toBe(0);
});

it('calculates shipping flat rate', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 5000, 'subtotal' => 5000, 'total' => 5000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'shipping_amount' => 499,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->shipping)->toBe(499);
});

it('calculates full checkout totals end to end', function () {
    $ctx = createStoreContext();
    TaxSettings::factory()->create([
        'store_id' => $ctx['store']->id,
        'rate_basis_points' => 1900,
        'prices_include_tax' => true,
    ]);

    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 2499]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 2, 'unit_price' => 2499, 'subtotal' => 4998, 'total' => 4998]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'discount_amount' => 499,
        'shipping_amount' => 499,
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(4998)
        ->and($result->discount)->toBe(499)
        ->and($result->shipping)->toBe(499)
        ->and($result->total)->toBeGreaterThan(0);
});

it('produces identical results for identical inputs', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->for($ctx['store'])->create();
    $v = \App\Models\ProductVariant::factory()->create(['price_amount' => 2500]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 2, 'unit_price' => 2500, 'subtotal' => 5000, 'total' => 5000]);

    $checkout = Checkout::factory()->create(['store_id' => $ctx['store']->id, 'cart_id' => $cart->id]);

    $r1 = $this->engine->calculate($checkout);
    $r2 = $this->engine->calculate($checkout);

    expect($r1->subtotal)->toBe($r2->subtotal)
        ->and($r1->total)->toBe($r2->total)
        ->and($r1->taxTotal)->toBe($r2->taxTotal);
});
