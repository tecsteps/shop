<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('calculates subtotal from line items', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variantA = ProductVariant::factory()->create(['price_amount' => 2499]);
    $variantB = ProductVariant::factory()->create(['price_amount' => 7999]);

    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variantA->id, 'quantity' => 2, 'unit_price_amount' => 2499, 'line_subtotal_amount' => 4998, 'line_total_amount' => 4998]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variantB->id, 'quantity' => 1, 'unit_price_amount' => 7999, 'line_subtotal_amount' => 7999, 'line_total_amount' => 7999]);

    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->subtotal)->toBe(12997);
});

it('calculates subtotal for a single line', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 1500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 3, 'unit_price_amount' => 1500, 'line_subtotal_amount' => 4500, 'line_total_amount' => 4500]);

    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(4500);
});

it('returns zero subtotal for empty cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(0);
});

it('applies percent discount correctly', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 10000, 'line_subtotal_amount' => 10000, 'line_total_amount' => 10000]);

    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'TEST10', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'status' => DiscountStatus::Active]);

    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id, 'discount_code' => 'TEST10']);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(1000);
});

it('applies fixed discount correctly', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 10000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 10000, 'line_subtotal_amount' => 10000, 'line_total_amount' => 10000]);

    Discount::factory()->fixed(500)->create(['store_id' => $this->store->id, 'code' => 'FIXED5']);

    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id, 'discount_code' => 'FIXED5']);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(500);
});

it('caps fixed discount at subtotal so it never goes negative', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 300]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 300, 'line_subtotal_amount' => 300, 'line_total_amount' => 300]);

    Discount::factory()->fixed(500)->create(['store_id' => $this->store->id, 'code' => 'BIG']);

    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id, 'discount_code' => 'BIG']);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(300)
        ->and($result->total)->toBeGreaterThanOrEqual(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 5000, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);
    Discount::factory()->freeShipping()->create(['store_id' => $this->store->id, 'code' => 'FREESHIP']);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $rate->id,
        'discount_code' => 'FREESHIP',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(0);
});

it('calculates tax exclusive correctly', function () {
    $taxCalc = new TaxCalculator;
    $tax = $taxCalc->addExclusive(10000, 1900);

    expect($tax)->toBe(1900);
});

it('extracts tax from inclusive price correctly', function () {
    $taxCalc = new TaxCalculator;
    $tax = $taxCalc->extractInclusive(11900, 1900);

    expect($tax)->toBe(1900);
});

it('returns zero tax when rate is zero', function () {
    $taxCalc = new TaxCalculator;
    $tax = $taxCalc->addExclusive(10000, 0);

    expect($tax)->toBe(0);
});

it('calculates shipping flat rate', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 1000, 'line_subtotal_amount' => 1000, 'line_total_amount' => 1000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => ShippingRateType::Flat, 'config_json' => ['amount' => 499]]);

    $calculator = new ShippingCalculator;
    $cost = $calculator->calculate($rate, $cart);

    expect($cost)->toBe(499);
});

it('calculates full checkout totals end to end', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 2499, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 2, 'unit_price_amount' => 2499, 'line_subtotal_amount' => 4998, 'line_total_amount' => 4998]);

    TaxSettings::factory()->inclusive()->create(['store_id' => $this->store->id, 'rate_percent' => 1900]);
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'WELCOME10', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $rate->id,
        'discount_code' => 'WELCOME10',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    // subtotal=4998, discount=499 (10%), discounted=4499, shipping=499
    // Tax is inclusive: extracted from discounted subtotal
    expect($result->subtotal)->toBe(4998)
        ->and($result->discount)->toBe(500) // round(4998 * 10 / 100) = 500
        ->and($result->shipping)->toBe(499);
});

it('handles rounding correctly with odd cent amounts', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $v1 = ProductVariant::factory()->create(['price_amount' => 3333]);
    $v2 = ProductVariant::factory()->create(['price_amount' => 3333]);
    $v3 = ProductVariant::factory()->create(['price_amount' => 3334]);

    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $v1->id, 'quantity' => 1, 'unit_price_amount' => 3333, 'line_subtotal_amount' => 3333, 'line_total_amount' => 3333]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $v2->id, 'quantity' => 1, 'unit_price_amount' => 3333, 'line_subtotal_amount' => 3333, 'line_total_amount' => 3333]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $v3->id, 'quantity' => 1, 'unit_price_amount' => 3334, 'line_subtotal_amount' => 3334, 'line_total_amount' => 3334]);

    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'ODD10', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10]);

    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id, 'discount_code' => 'ODD10']);

    $result = app(PricingEngine::class)->calculate($checkout);

    // Total subtotal = 10000, 10% = 1000
    expect($result->discount)->toBe(1000);
});

it('produces identical results for identical inputs', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 2500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 2, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);

    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id]);

    $engine = app(PricingEngine::class);
    $result1 = $engine->calculate($checkout);
    $result2 = $engine->calculate($checkout);

    expect($result1->toArray())->toBe($result2->toArray());
});
