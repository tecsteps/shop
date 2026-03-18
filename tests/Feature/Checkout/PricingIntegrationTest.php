<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

function createPricingIntegrationContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Pricing Test',
        'handle' => 'pricing-test-'.rand(1000, 9999),
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
        'requires_shipping' => true,
        'weight_g' => 500,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->id,
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

    TaxSettings::create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    return array_merge($ctx, compact('product', 'variant', 'cart', 'zone', 'rate'));
}

it('calculates simple checkout totals correctly', function () {
    $ctx = createPricingIntegrationContext();

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $ctx['rate']->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // subtotal = 2 * 2500 = 5000
    // shipping = 499 (flat)
    // tax = round(5000 * 1900 / 10000) = 950 (on discounted subtotal only)
    // total = 5000 + 499 + 950 = 6449
    expect($result->subtotal)->toBe(5000)
        ->and($result->discount)->toBe(0)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(950)
        ->and($result->total)->toBe(6449);
});

it('applies discount code and recalculates correctly', function () {
    $ctx = createPricingIntegrationContext();

    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $ctx['rate']->id,
        'discount_code' => 'SAVE10',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // subtotal = 5000, discount = 500 (10%), discounted = 4500
    // shipping = 499, tax = round(4500 * 1900 / 10000) = 855
    // total = 4500 + 499 + 855 = 5854
    expect($result->subtotal)->toBe(5000)
        ->and($result->discount)->toBe(500)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(855)
        ->and($result->total)->toBe(5854);
});

it('stores pricing snapshot in totals_json via checkout service', function () {
    $ctx = createPricingIntegrationContext();
    $checkoutService = app(CheckoutService::class);

    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkout->refresh();
    expect($checkout->totals_json)->not->toBeNull()
        ->and($checkout->totals_json['subtotal'])->toBe(5000)
        ->and($checkout->totals_json['currency'])->toBe('EUR');
});

it('recalculates when shipping method changes', function () {
    $ctx = createPricingIntegrationContext();

    $expressRate = ShippingRate::create([
        'zone_id' => $ctx['zone']->id,
        'name' => 'Express',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
        'is_active' => true,
    ]);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    // Select standard shipping first
    $checkoutService->setShippingMethod($checkout->fresh(), $ctx['rate']->id);
    $checkout->refresh();
    $standardTotal = $checkout->totals_json['total'];

    // Now change to express - need to reset status for re-selection
    $checkout->update(['status' => CheckoutStatus::Addressed]);
    $checkoutService->setShippingMethod($checkout->fresh(), $expressRate->id);
    $checkout->refresh();
    $expressTotal = $checkout->totals_json['total'];

    // Express should be 500 more (999 - 499)
    expect($expressTotal)->toBeGreaterThan($standardTotal);
});

it('handles prices-include-tax mode in full pipeline', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Inclusive Tax Product',
        'handle' => 'inclusive-tax-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 11900, // 100 EUR gross (incl 19% tax)
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 11900,
        'line_subtotal_amount' => 11900,
        'line_discount_amount' => 0,
        'line_total_amount' => 11900,
    ]);

    TaxSettings::create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // Tax-inclusive: total = subtotal (tax already inside), not subtotal + tax
    // gross = 11900, net = intdiv(11900 * 10000, 11900) = 10000, tax = 1900
    expect($result->subtotal)->toBe(11900)
        ->and($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900); // total = discounted_subtotal + shipping, no tax added on top
});
