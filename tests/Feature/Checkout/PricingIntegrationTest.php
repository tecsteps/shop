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

function createPricingIntegrationContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
        'zone' => $zone,
        'rate' => $rate,
    ]);
}

it('calculates correct totals for a simple checkout', function () {
    $ctx = createPricingIntegrationContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1000],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // subtotal: 10000
    // shipping: 499
    // tax on products: intdiv(10000 * 1000, 10000) = 1000
    // tax on shipping: intdiv(499 * 1000, 10000) = 49
    // total: 10000 + 499 + 1000 + 49 = 11548

    expect($result->subtotal)->toBe(10000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1049)
        ->and($result->total)->toBe(11548);
});

it('applies discount code and recalculates', function () {
    $ctx = createPricingIntegrationContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'TENOFF',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'TENOFF',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000)
        ->and($result->total)->toBe(9000);
});

it('stores pricing snapshot in totals_json', function () {
    $ctx = createPricingIntegrationContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $engine->calculate($checkout);

    $checkout->refresh();

    expect($checkout->totals_json)->toHaveKeys([
        'subtotal', 'discount', 'shipping', 'tax_lines', 'tax_total', 'total', 'currency',
    ]);
});

it('recalculates on shipping method change', function () {
    $ctx = createPricingIntegrationContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);

    // Without shipping
    $result1 = $engine->calculate($checkout);
    expect($result1->shipping)->toBe(0);

    // With shipping
    $checkout->update(['shipping_method_id' => $rate->id]);
    $result2 = $engine->calculate($checkout->fresh());

    expect($result2->shipping)->toBe(499)
        ->and($result2->total)->toBe($result1->total + 499);
});

it('handles prices-include-tax correctly', function () {
    $ctx = createPricingIntegrationContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => true,
        'config_json' => ['default_rate' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // Prices include tax, so total should equal subtotal (no extra tax added)
    expect($result->total)->toBe(10000)
        ->and($result->taxTotal)->toBeGreaterThan(0);
});
