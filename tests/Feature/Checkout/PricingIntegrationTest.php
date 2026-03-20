<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\PricingEngine;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->pricingEngine = app(PricingEngine::class);
});

it('calculates full pricing pipeline correctly', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
        'prices_include_tax' => false,
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'TEN',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => 'addressed',
        'discount_code' => 'TEN',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    // Subtotal: 10000, Discount: 1000, Discounted: 9000, Shipping: 499, Tax on 9499: 1805, Total: 11304
    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1805)
        ->and($result->total)->toBe(11304);
});

it('handles free shipping discount in pricing', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 0,
        'is_active' => false,
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
        'code' => 'FREESHIP',
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => 'addressed',
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->shipping)->toBe(0)
        ->and($result->discount)->toBe(0);
});

it('recalculates when shipping method changes', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
        'prices_include_tax' => false,
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate1 = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'config_json' => ['amount' => 499],
    ]);
    $rate2 = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Express',
        'config_json' => ['amount' => 899],
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => 'addressed',
        'shipping_method_id' => $rate1->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result1 = $this->pricingEngine->calculate($checkout);

    $checkout->update(['shipping_method_id' => $rate2->id]);
    $result2 = $this->pricingEngine->calculate($checkout->fresh());

    expect($result1->shipping)->toBe(499)
        ->and($result2->shipping)->toBe(899);
});

it('calculates without discount code', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 0,
        'is_active' => false,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->subtotal)->toBe(5000)
        ->and($result->discount)->toBe(0)
        ->and($result->total)->toBe(5000);
});

it('handles tax-inclusive pricing in full pipeline', function () {
    TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 11900]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 11900,
        'line_subtotal_amount' => 11900,
        'line_total_amount' => 11900,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->subtotal)->toBe(11900)
        ->and($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});
