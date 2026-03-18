<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;

it('returns available shipping rates for address', function () {
    $ctx = createStoreContext();

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DE',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard Shipping',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($ctx['store'], ['country' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard Shipping');
});

it('returns empty when no zone matches address', function () {
    $ctx = createStoreContext();

    ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DE Only',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $calculator = app(ShippingCalculator::class);
    $rates = $calculator->getAvailableRates($ctx['store'], ['country' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates flat rate correctly', function () {
    $ctx = createStoreContext();

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DE',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Test',
        'handle' => 'shipping-test-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->shipping)->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    $ctx = createStoreContext();

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DE',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Weight-based',
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [
            ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
            ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
        ]],
        'is_active' => true,
    ]);

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Heavy Item',
        'handle' => 'heavy-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'weight_g' => 250,
        'requires_shipping' => true,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3, // 750g total
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 7500,
        'line_discount_amount' => 0,
        'line_total_amount' => 7500,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->shipping)->toBe(899);
});

it('returns zero shipping when all items are digital', function () {
    $ctx = createStoreContext();

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Digital Product',
        'handle' => 'digital-shipping-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
        'weight_g' => 0,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_discount_amount' => 0,
        'line_total_amount' => 1000,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->shipping)->toBe(0);
});
