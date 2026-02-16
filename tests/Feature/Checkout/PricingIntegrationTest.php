<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use App\Services\TaxCalculator;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->engine = new PricingEngine(new TaxCalculator);
});

it('calculates correct totals for a simple checkout', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'rate_basis_points' => 1900,
        'prices_include_tax' => false,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 2, 'unit_price' => 2500, 'subtotal' => 5000, 'total' => 5000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'shipping_amount' => 499,
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(5000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1044) // 19% of 5499
        ->and($result->total)->toBe(6543);
});

it('applies discount code and recalculates', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 10000]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000, 'total' => 10000]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'discount_amount' => 1000,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->discount)->toBe(1000);
});

it('handles prices-include-tax correctly', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'rate_basis_points' => 1900,
        'prices_include_tax' => true,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 11900]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 11900, 'subtotal' => 11900, 'total' => 11900]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
    ]);

    $result = $this->engine->calculate($checkout);
    expect($result->taxTotal)->toBe(1900);
});
