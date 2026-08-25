<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\PricingEngine;

/**
 * @return array{0: Checkout, 1: Store}
 */
function makePricingScenario(bool $pricesIncludeTax = true): array
{
    $store = Store::factory()->create(['default_currency' => 'USD']);
    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => $pricesIncludeTax,
        'config_json' => ['default_tax_rate' => 1900],
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);

    foreach ([2499, 2499] as $price) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => $price]);
        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price,
            'line_total_amount' => $price,
        ]);
    }

    Discount::factory()->create(['store_id' => $store->id, 'code' => 'WELCOME10', 'value_type' => 'percent', 'value_amount' => 10]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'discount_code' => 'WELCOME10',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country_code' => 'DE'],
    ]);

    return [$checkout, $store];
}

it('calculates full checkout totals end to end', function () {
    [$checkout] = makePricingScenario(true);
    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(4998);
    expect($result->discount)->toBe(499);
    expect($result->shipping)->toBe(499);
    expect($result->taxTotal)->toBe(798);
    expect($result->total)->toBe(5796);
});

it('calculates tax exclusive correctly', function () {
    [$checkout] = makePricingScenario(false);
    $result = app(PricingEngine::class)->calculate($checkout);

    // subtotal 4998 - discount 499 = 4499 + shipping 499 = 4998; tax = intdiv(4998*1900,10000) = 949
    expect($result->subtotal)->toBe(4998);
    expect($result->discount)->toBe(499);
    expect($result->shipping)->toBe(499);
    expect($result->taxTotal)->toBe(949);
});

it('produces identical results for identical inputs', function () {
    [$checkout] = makePricingScenario(true);
    $engine = app(PricingEngine::class);

    $first = $engine->calculate($checkout);
    $second = $engine->calculate($checkout->fresh());

    expect($first->toArray())->toBe($second->toArray());
});

it('returns zero subtotal for empty cart', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $checkout = Checkout::factory()->create(['store_id' => $store->id, 'cart_id' => $cart->id]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(0);
    expect($result->total)->toBe(0);
});
