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

it('calculates correct totals for a simple checkout', function () {
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
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => 'addressed',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->subtotal)->toBe(5000)
        ->and($result->discount)->toBe(0)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1045)
        ->and($result->total)->toBe(6544);
});

it('applies discount code and recalculates', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 0,
        'is_active' => false,
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
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => 'addressed',
        'discount_code' => 'SAVE10',
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000);
});

it('stores pricing snapshot in totals_json', function () {
    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate' => 1900]);

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
        'status' => 'started',
    ]);

    $result = $this->pricingEngine->calculate($checkout);
    $array = $result->toArray();

    expect($array)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax_lines', 'tax_total', 'total', 'currency']);
});

it('handles prices-include-tax correctly', function () {
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
        'status' => 'started',
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('discount is applied before tax', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
        'prices_include_tax' => false,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 10000,
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
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    // Subtotal: 10000, discount: 1000, discounted: 9000, tax on 9000 = 1710, total = 10710
    expect($result->discount)->toBe(1000)
        ->and($result->taxTotal)->toBe(1710);
});

it('same inputs produce same outputs (determinism)', function () {
    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate' => 1900]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 3000,
        'line_subtotal_amount' => 6000,
        'line_total_amount' => 6000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => 'started',
    ]);

    $result1 = $this->pricingEngine->calculate($checkout);
    $result2 = $this->pricingEngine->calculate($checkout);

    expect($result1->toArray())->toBe($result2->toArray());
});

it('PricingResult contains all required fields', function () {
    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate' => 1900]);

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
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->subtotal)->toBeInt()
        ->and($result->discount)->toBeInt()
        ->and($result->shipping)->toBeInt()
        ->and($result->taxLines)->toBeArray()
        ->and($result->taxTotal)->toBeInt()
        ->and($result->total)->toBeInt()
        ->and($result->currency)->toBeString();
});
