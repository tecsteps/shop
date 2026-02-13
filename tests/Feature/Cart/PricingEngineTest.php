<?php

use App\Enums\DiscountValueType;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->engine = app(PricingEngine::class);
});

function createCartWithPrices(Store $store, array $prices): Cart
{
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    foreach ($prices as $price) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $price,
            'status' => VariantStatus::Active,
        ]);

        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price,
            'line_total_amount' => $price,
        ]);
    }

    return $cart;
}

test('calculates subtotal from line items', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $product1 = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant1 = ProductVariant::factory()->create(['product_id' => $product1->id, 'price_amount' => 2499]);

    $product2 = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product2->id, 'price_amount' => 7999]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant1->id,
        'quantity' => 2,
        'unit_price_amount' => 2499,
        'line_subtotal_amount' => 4998,
        'line_total_amount' => 4998,
    ]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant2->id,
        'quantity' => 1,
        'unit_price_amount' => 7999,
        'line_subtotal_amount' => 7999,
        'line_total_amount' => 7999,
    ]);

    $subtotal = $this->engine->calculateSubtotal($cart);

    expect($subtotal)->toBe(12997);
});

test('calculates subtotal for a single line', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 1500,
        'line_subtotal_amount' => 4500,
        'line_total_amount' => 4500,
    ]);

    expect($this->engine->calculateSubtotal($cart))->toBe(4500);
});

test('returns zero subtotal for empty cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect($this->engine->calculateSubtotal($cart))->toBe(0);
});

test('applies percent discount correctly', function () {
    $cart = createCartWithPrices($this->store, [10000]);
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $result = $this->engine->calculateForCart($cart, $discount);

    expect($result->subtotal)->toBe(10000);
    expect($result->discount)->toBe(1000);
    expect($result->total)->toBe(9000);
});

test('applies fixed discount correctly', function () {
    $cart = createCartWithPrices($this->store, [10000]);
    $discount = Discount::factory()->fixed(500)->create(['store_id' => $this->store->id]);

    $result = $this->engine->calculateForCart($cart, $discount);

    expect($result->discount)->toBe(500);
    expect($result->total)->toBe(9500);
});

test('caps fixed discount at subtotal so it never goes negative', function () {
    $cart = createCartWithPrices($this->store, [300]);
    $discount = Discount::factory()->fixed(500)->create(['store_id' => $this->store->id]);

    $result = $this->engine->calculateForCart($cart, $discount);

    expect($result->discount)->toBe(300);
    expect($result->total)->toBe(0);
});

test('applies free shipping discount by zeroing shipping', function () {
    $cart = createCartWithPrices($this->store, [5000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $discount = Discount::factory()->freeShipping()->create(['store_id' => $this->store->id]);

    $result = $this->engine->calculateForCart($cart, $discount, $rate);

    expect($result->shipping)->toBe(0);
});

test('calculates tax exclusive correctly', function () {
    $cart = createCartWithPrices($this->store, [10000]);
    $taxSettings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result = $this->engine->calculateForCart($cart, taxSettings: $taxSettings);

    expect($result->taxTotal)->toBe(1900);
    expect($result->total)->toBe(11900);
});

test('extracts tax from inclusive price correctly', function () {
    $cart = createCartWithPrices($this->store, [11900]);
    $taxSettings = TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result = $this->engine->calculateForCart($cart, taxSettings: $taxSettings);

    expect($result->taxTotal)->toBe(1900);
    // With inclusive pricing, total = subtotal (tax is already included)
    expect($result->total)->toBe(11900);
});

test('returns zero tax when rate is zero', function () {
    $cart = createCartWithPrices($this->store, [10000]);
    $taxSettings = TaxSettings::factory()->zeroRate()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->engine->calculateForCart($cart, taxSettings: $taxSettings);

    expect($result->taxTotal)->toBe(0);
});

test('calculates shipping flat rate', function () {
    $cart = createCartWithPrices($this->store, [5000]);
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $result = $this->engine->calculateForCart($cart, shippingRate: $rate);

    expect($result->shipping)->toBe(499);
});

test('produces identical results for identical inputs', function () {
    $cart = createCartWithPrices($this->store, [5000, 3000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $taxSettings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result1 = $this->engine->calculateForCart($cart, shippingRate: $rate, taxSettings: $taxSettings);
    $result2 = $this->engine->calculateForCart($cart, shippingRate: $rate, taxSettings: $taxSettings);

    expect($result1->toArray())->toBe($result2->toArray());
});

test('result contains correct currency', function () {
    $cart = createCartWithPrices($this->store, [5000]);

    $result = $this->engine->calculateForCart($cart);

    expect($result->currency)->toBe('USD');
});

test('pricing result toArray returns expected structure', function () {
    $cart = createCartWithPrices($this->store, [5000]);
    $taxSettings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result = $this->engine->calculateForCart($cart, taxSettings: $taxSettings);
    $array = $result->toArray();

    expect($array)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax_lines', 'tax_total', 'total', 'currency']);
});
