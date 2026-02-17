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
use App\ValueObjects\PricingResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates subtotal from line items', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 1000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 3000,
        'line_total_amount' => 3000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result)->toBeInstanceOf(PricingResult::class)
        ->and($result->subtotal)->toBe(3000)
        ->and($result->currency)->toBe('EUR');
});

it('sums multiple line subtotals', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $v1 = ProductVariant::factory()->create(['price_amount' => 1000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $v1->id,
        'quantity' => 2,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 2000,
    ]);

    $v2 = ProductVariant::factory()->create(['price_amount' => 500]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $v2->id,
        'quantity' => 1,
        'unit_price_amount' => 500,
        'line_subtotal_amount' => 500,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(2500);
});

it('applies percent discount', function () {
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

    expect($result->discount)->toBe(500)
        ->and($result->total)->toBe(4500);
});

it('includes shipping in total', function () {
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
    $variant = ProductVariant::factory()->create(['price_amount' => 2000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 2000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(499)
        ->and($result->total)->toBe(2499);
});

it('calculates exclusive tax', function () {
    $store = Store::factory()->create();
    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1900],
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 1000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(190)
        ->and($result->total)->toBe(1190);
});

it('calculates inclusive tax without adding to total', function () {
    $store = Store::factory()->create();
    TaxSettings::factory()->inclusive()->create([
        'store_id' => $store->id,
        'config_json' => ['default_rate_bps' => 1900],
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 1190]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1190,
        'line_subtotal_amount' => 1190,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(190)
        ->and($result->total)->toBe(1190); // Total stays at gross amount
});

it('applies free shipping discount', function () {
    $store = Store::factory()->create();
    Discount::factory()->freeShipping()->create([
        'store_id' => $store->id,
        'code' => 'FREESHIP',
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 500],
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
        'discount_code' => 'FREESHIP',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(0)
        ->and($result->discount)->toBe(0); // Free shipping does not discount items
});

it('produces a valid toArray output', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 1000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);
    $array = $result->toArray();

    expect($array)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax_lines', 'tax_total', 'total', 'currency']);
});

it('handles empty cart gracefully', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(0)
        ->and($result->total)->toBe(0);
});

it('ignores invalid discount code gracefully', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 2000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 2000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'discount_code' => 'INVALIDCODE',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(0)
        ->and($result->total)->toBe(2000);
});

it('calculates discount then tax correctly', function () {
    $store = Store::factory()->create();
    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1000],
    ]);

    Discount::factory()->create([
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

    // subtotal=10000, discount=5000, discounted=5000, tax(10%)=500, total=5500
    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(5000)
        ->and($result->taxTotal)->toBe(500)
        ->and($result->total)->toBe(5500);
});

it('calculates full pipeline with shipping and tax', function () {
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
        'config_json' => ['amount' => 500],
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    // subtotal=10000, shipping=500, tax(19% of 10000)=1900, total=10000+500+1900=12400
    expect($result->subtotal)->toBe(10000)
        ->and($result->shipping)->toBe(500)
        ->and($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(12400);
});

it('returns correct currency from cart', function () {
    $store = Store::factory()->create(['default_currency' => 'USD']);
    $cart = Cart::factory()->create(['store_id' => $store->id, 'currency' => 'USD']);
    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->currency)->toBe('USD');
});
