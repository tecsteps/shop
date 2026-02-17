<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds exclusive tax to total', function () {
    $store = Store::factory()->create();
    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1900],
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
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('extracts inclusive tax without increasing total', function () {
    $store = Store::factory()->create();
    TaxSettings::factory()->inclusive()->create([
        'store_id' => $store->id,
        'config_json' => ['default_rate_bps' => 1900],
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 11900]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 11900,
        'line_subtotal_amount' => 11900,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('returns zero tax when no tax settings exist', function () {
    $store = Store::factory()->create();

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
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(0)
        ->and($result->taxLines)->toBeEmpty()
        ->and($result->total)->toBe(5000);
});

it('calculates tax on discounted amount', function () {
    $store = Store::factory()->create();
    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1000],
    ]);

    \App\Models\Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'HALF',
        'value_amount' => 50,
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
        'discount_code' => 'HALF',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    // subtotal=10000, discount(50%)=5000, discounted=5000, tax(10%)=500
    expect($result->discount)->toBe(5000)
        ->and($result->taxTotal)->toBe(500)
        ->and($result->total)->toBe(5500);
});
