<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\ProductVariant;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use App\Services\TaxCalculator;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->engine = new PricingEngine(new TaxCalculator);
});

it('calculates exclusive tax correctly at checkout', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'rate_basis_points' => 1900,
        'prices_include_tax' => false,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $v = ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 5000, 'subtotal' => 5000, 'total' => 5000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'shipping_amount' => 499,
    ]);

    $result = $this->engine->calculate($checkout);
    // 19% of (5000 + 0 discount) = 950 for items, shipping not taxed by default
    expect($result->taxTotal)->toBe(950);
});

it('extracts inclusive tax correctly at checkout', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'rate_basis_points' => 1900,
        'prices_include_tax' => true,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $v = ProductVariant::factory()->create(['price_amount' => 11900]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 11900, 'subtotal' => 11900, 'total' => 11900]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->taxTotal)->toBe(1900);
});

it('applies zero tax when no tax settings exist', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $v = ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000, 'total' => 10000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->taxTotal)->toBe(0);
});

it('stores tax lines in results', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'rate_basis_points' => 1900,
        'tax_name' => 'VAT',
        'prices_include_tax' => false,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $v = ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $v->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000, 'total' => 10000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->taxLines)->toHaveCount(1)
        ->and($result->taxLines[0]->name)->toBe('VAT')
        ->and($result->taxLines[0]->rate)->toBe(1900);
});
