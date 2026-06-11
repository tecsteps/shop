<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Build a checkout whose cart holds one line per [price, quantity] pair.
 *
 * @param  list<array{0: int, 1: int}>  $lines
 * @param  array<string, mixed>  $checkoutAttributes
 * @param  array<string, mixed>  $variantAttributes
 * @return array{store: Store, cart: Cart, checkout: Checkout}
 */
function makePricedCheckout(array $lines, array $checkoutAttributes = [], array $variantAttributes = []): array
{
    $store = Store::factory()->create();
    $cart = Cart::factory()->for($store)->create();

    foreach ($lines as [$price, $quantity]) {
        $variant = createPurchasableVariant($store, $price, 100, $variantAttributes);

        CartLine::factory()->for($cart)->priced($price, $quantity)->create([
            'variant_id' => $variant->getKey(),
        ]);
    }

    $checkout = Checkout::factory()->for($store)->for($cart)->create($checkoutAttributes);

    return ['store' => $store, 'cart' => $cart, 'checkout' => $checkout];
}

/**
 * Create a flat shipping rate within a DE zone for the store.
 */
function makeFlatRateForStore(Store $store, int $amount): ShippingRate
{
    $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);

    return ShippingRate::factory()->for($zone, 'zone')->flatAmount($amount)->create();
}

it('calculates subtotal from line items', function () {
    ['checkout' => $checkout] = makePricedCheckout([[2499, 2], [7999, 1]]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(12997);
});

it('calculates subtotal for a single line', function () {
    ['checkout' => $checkout] = makePricedCheckout([[1500, 3]]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(4500);
});

it('returns zero subtotal for empty cart', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->for($store)->create();
    $checkout = Checkout::factory()->for($store)->for($cart)->create();

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(0);
    expect($result->total)->toBe(0);
});

it('applies percent discount correctly', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[10000, 1]]);
    Discount::factory()->for($store)->create(['code' => 'SAVE10', 'value_amount' => 10]);
    $checkout->update(['discount_code' => 'SAVE10']);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(1000);
    expect($result->subtotal - $result->discount)->toBe(9000);
});

it('applies fixed discount correctly', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[10000, 1]]);
    Discount::factory()->for($store)->fixed(500)->create(['code' => '5OFF']);
    $checkout->update(['discount_code' => '5OFF']);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(500);
    expect($result->subtotal - $result->discount)->toBe(9500);
});

it('caps fixed discount at subtotal so it never goes negative', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[300, 1]]);
    Discount::factory()->for($store)->fixed(500)->create(['code' => '5OFF']);
    $checkout->update(['discount_code' => '5OFF']);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->discount)->toBe(300);
    expect($result->total)->toBe(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[5000, 1]]);
    $rate = makeFlatRateForStore($store, 499);
    Discount::factory()->for($store)->freeShipping()->create(['code' => 'FREESHIP']);
    $checkout->update(['discount_code' => 'FREESHIP', 'shipping_method_id' => $rate->getKey()]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(0);
});

it('calculates tax exclusive correctly', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[10000, 1]]);
    TaxSettings::factory()->for($store)->rateBasisPoints(1900)->create();

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(1900);
    expect($result->total)->toBe(11900);
});

it('extracts tax from inclusive price correctly', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[11900, 1]]);
    TaxSettings::factory()->for($store)->rateBasisPoints(1900)->pricesIncludeTax()->create();

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(1900);
    expect($result->total)->toBe(11900);
    expect($result->total - $result->taxTotal)->toBe(10000);
});

it('returns zero tax when rate is zero', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[10000, 1]]);
    TaxSettings::factory()->for($store)->rateBasisPoints(0)->create();

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->taxTotal)->toBe(0);
});

it('calculates shipping flat rate', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[2500, 1]]);
    $rate = makeFlatRateForStore($store, 499);
    $checkout->update(['shipping_method_id' => $rate->getKey()]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(499);
});

it('calculates full checkout totals end to end', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[2499, 1], [2499, 1]]);
    $rate = makeFlatRateForStore($store, 499);
    TaxSettings::factory()->for($store)->rateBasisPoints(1900)->pricesIncludeTax()->create();
    Discount::factory()->for($store)->create(['code' => 'WELCOME10', 'value_amount' => 10]);
    $checkout->update(['discount_code' => 'WELCOME10', 'shipping_method_id' => $rate->getKey()]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(4998);
    expect($result->discount)->toBe(499);
    expect($result->shipping)->toBe(499);
    // Tax extracted from the gross 4499 + 499 = 4998: 4998 - intdiv(4998 * 10000, 11900) = 798.
    expect($result->taxTotal)->toBe(798);
    expect($result->total)->toBe(4998);
});

it('handles rounding correctly with odd cent amounts', function () {
    ['store' => $store, 'cart' => $cart, 'checkout' => $checkout] = makePricedCheckout([[1111, 1], [2222, 1], [3333, 1]]);
    Discount::factory()->for($store)->create(['code' => 'ODD15', 'value_amount' => 15]);
    $checkout->update(['discount_code' => 'ODD15']);

    $result = app(PricingEngine::class)->calculate($checkout);

    $allocatedTotal = (int) $cart->lines()->sum('line_discount_amount');

    expect($result->discount)->toBe(999);
    expect($allocatedTotal)->toBe($result->discount);
});

it('produces identical results for identical inputs', function () {
    ['store' => $store, 'checkout' => $checkout] = makePricedCheckout([[2499, 2], [7999, 1]]);
    $rate = makeFlatRateForStore($store, 499);
    TaxSettings::factory()->for($store)->rateBasisPoints(1900)->create();
    Discount::factory()->for($store)->create(['code' => 'SAVE10', 'value_amount' => 10]);
    $checkout->update(['discount_code' => 'SAVE10', 'shipping_method_id' => $rate->getKey()]);

    $first = app(PricingEngine::class)->calculate($checkout);
    $second = app(PricingEngine::class)->calculate($checkout->refresh());

    expect($first->toArray())->toBe($second->toArray());
});
