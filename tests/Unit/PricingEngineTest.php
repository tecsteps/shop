<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createPricingContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();

    return array_merge($ctx, ['product' => $product]);
}

function createCheckoutWithLines(array $ctx, array $lineSpecs): Checkout
{
    $store = $ctx['store'];
    $product = $ctx['product'];

    $cart = Cart::factory()->for($store)->create();

    foreach ($lineSpecs as $spec) {
        $variant = ProductVariant::factory()->for($product)->create([
            'price_amount' => $spec['price'],
            'status' => VariantStatus::Active,
            'weight_g' => $spec['weight_g'] ?? 500,
        ]);

        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => $spec['qty'],
            'unit_price_amount' => $spec['price'],
            'line_subtotal_amount' => $spec['price'] * $spec['qty'],
            'line_total_amount' => $spec['price'] * $spec['qty'],
        ]);
    }

    return Checkout::factory()->for($store)->create([
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);
}

it('calculates subtotal from line items', function () {
    $ctx = createPricingContext();
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 2499, 'qty' => 2],
        ['price' => 7999, 'qty' => 1],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->subtotal)->toBe(12997);
});

it('calculates subtotal for a single line', function () {
    $ctx = createPricingContext();
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 1500, 'qty' => 3],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->subtotal)->toBe(4500);
});

it('returns zero subtotal for empty cart', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $cart = Cart::factory()->for($store)->create();

    $checkout = Checkout::factory()->for($store)->create([
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->subtotal)->toBe(0)
        ->and($result->total)->toBe(0);
});

it('applies percent discount correctly', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 10000, 'qty' => 1],
    ]);

    Discount::factory()->for($store)->create([
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
    ]);

    $checkout->update(['discount_code' => 'SAVE10']);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout->fresh());

    expect($result->discount)->toBe(1000)
        ->and($result->total)->toBe(9000);
});

it('applies fixed discount correctly', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 10000, 'qty' => 1],
    ]);

    Discount::factory()->for($store)->create([
        'code' => 'FLAT500',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'status' => DiscountStatus::Active,
    ]);

    $checkout->update(['discount_code' => 'FLAT500']);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout->fresh());

    expect($result->discount)->toBe(500)
        ->and($result->total)->toBe(9500);
});

it('caps fixed discount at subtotal so it never goes negative', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 300, 'qty' => 1],
    ]);

    Discount::factory()->for($store)->create([
        'code' => 'BIG500',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'status' => DiscountStatus::Active,
    ]);

    $checkout->update(['discount_code' => 'BIG500']);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout->fresh());

    expect($result->discount)->toBe(300)
        ->and($result->total)->toBe(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 5000, 'qty' => 1],
    ]);

    Discount::factory()->for($store)->create([
        'code' => 'FREESHIP',
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
        'status' => DiscountStatus::Active,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
    ]);

    $checkout->update([
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout->fresh());

    expect($result->shipping)->toBe(0)
        ->and($result->total)->toBe(5000);
});

it('calculates tax exclusive correctly', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 10000, 'qty' => 1],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('extracts tax from inclusive price correctly', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 11900, 'qty' => 1],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => true,
        'config_json' => ['default_rate' => 1900],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('returns zero tax when rate is zero', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 10000, 'qty' => 1],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'config_json' => ['default_rate' => 0],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(0)
        ->and($result->total)->toBe(10000);
});

it('calculates shipping flat rate', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 5000, 'qty' => 1],
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $checkout->update(['shipping_method_id' => $rate->id]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout->fresh());

    expect($result->shipping)->toBe(499)
        ->and($result->total)->toBe(5499);
});

it('calculates full checkout totals end to end', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];

    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 5000, 'qty' => 2],
        ['price' => 3000, 'qty' => 1],
    ]);

    Discount::factory()->for($store)->create([
        'code' => 'TENOFF',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1000],
    ]);

    $checkout->update([
        'discount_code' => 'TENOFF',
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout->fresh());

    // subtotal: 5000*2 + 3000*1 = 13000
    // discount: 10% of 13000 = 1300
    // discounted: 11700
    // shipping: 499
    // tax on products: intdiv(11700 * 1000, 10000) = 1170
    // tax on shipping: intdiv(499 * 1000, 10000) = 49
    // total: 11700 + 499 + 1170 + 49 = 13418

    expect($result->subtotal)->toBe(13000)
        ->and($result->discount)->toBe(1300)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1219)
        ->and($result->total)->toBe(13418);
});

it('handles rounding correctly with odd cent amounts', function () {
    $ctx = createPricingContext();
    $store = $ctx['store'];
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 999, 'qty' => 3],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // subtotal: 999*3 = 2997
    // tax: intdiv(2997 * 1900, 10000) = intdiv(5694300, 10000) = 569
    // total: 2997 + 569 = 3566

    expect($result->subtotal)->toBe(2997)
        ->and($result->taxTotal)->toBe(569)
        ->and($result->total)->toBe(3566);
});

it('produces identical results for identical inputs', function () {
    $ctx = createPricingContext();
    $checkout = createCheckoutWithLines($ctx, [
        ['price' => 2500, 'qty' => 2],
    ]);

    $engine = app(PricingEngine::class);
    $result1 = $engine->calculate($checkout);
    $result2 = $engine->calculate($checkout->fresh());

    expect($result1->toArray())->toBe($result2->toArray());
});
