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
use App\Services\PricingEngine;

function createPricingContext(array $overrides = []): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Test Product',
        'handle' => 'test-product',
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => $overrides['price'] ?? 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'requires_shipping' => $overrides['requires_shipping'] ?? true,
        'weight_g' => $overrides['weight_g'] ?? 500,
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

    $qty = $overrides['quantity'] ?? 2;
    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => $qty,
        'unit_price_amount' => $variant->price_amount,
        'line_subtotal_amount' => $variant->price_amount * $qty,
        'line_discount_amount' => 0,
        'line_total_amount' => $variant->price_amount * $qty,
    ]);

    return array_merge($ctx, compact('product', 'variant', 'cart'));
}

it('calculates correct totals for a simple checkout without discount', function () {
    $ctx = createPricingContext(['price' => 2500, 'quantity' => 2]);

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

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->subtotal)->toBe(5000)
        ->and($result->discount)->toBe(0)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(950) // round(5000 * 1900 / 10000) - tax on discounted subtotal only
        ->and($result->total)->toBe(6449); // 5000 + 499 + 950
});

it('applies percent discount correctly', function () {
    $ctx = createPricingContext(['price' => 5000, 'quantity' => 2]);

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
        'status' => CheckoutStatus::Started,
        'discount_code' => 'SAVE10',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000);
});

it('applies fixed discount correctly', function () {
    $ctx = createPricingContext(['price' => 5000, 'quantity' => 2]);

    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => '5OFF',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => '5OFF',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->discount)->toBe(500)
        ->and($result->subtotal - $result->discount)->toBe(9500);
});

it('caps fixed discount at subtotal so it never goes negative', function () {
    $ctx = createPricingContext(['price' => 150, 'quantity' => 2]);

    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'BIG',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'BIG',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->discount)->toBe(300)
        ->and($result->subtotal - $result->discount)->toBe(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    $ctx = createPricingContext(['price' => 2500, 'quantity' => 2]);

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

    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'FREESHIP',
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $rate->id,
        'discount_code' => 'FREESHIP',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->shipping)->toBe(0);
});

it('calculates tax exclusive correctly', function () {
    $ctx = createPricingContext(['price' => 5000, 'quantity' => 2]);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900) // round(10000 * 1900 / 10000)
        ->and($result->total)->toBe(11900);
});

it('extracts tax from inclusive price correctly', function () {
    $ctx = createPricingContext(['price' => 5950, 'quantity' => 2]);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // gross = 11900, net = intdiv(11900 * 10000, 11900) = 10000, tax = 1900
    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900); // inclusive: total = discounted_subtotal + shipping
});

it('returns zero tax when rate is zero', function () {
    $ctx = createPricingContext(['price' => 5000, 'quantity' => 2]);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 0],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(0);
});

it('calculates shipping flat rate', function () {
    $ctx = createPricingContext(['price' => 2500, 'quantity' => 2]);

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

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->shipping)->toBe(499);
});

it('calculates full checkout totals end to end', function () {
    $ctx = createPricingContext(['price' => 2499, 'quantity' => 2]);

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

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'WELCOME10',
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
        'shipping_method_id' => $rate->id,
        'discount_code' => 'WELCOME10',
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    $subtotal = 4998; // 2499 * 2
    $discount = (int) round($subtotal * 10 / 100); // 500
    $discountedSubtotal = $subtotal - $discount; // 4498
    $shipping = 499;
    $taxableAmount = $discountedSubtotal; // tax not on shipping by default
    $tax = (int) round($taxableAmount * 1900 / 10000);
    $total = $discountedSubtotal + $shipping + $tax;

    expect($result->subtotal)->toBe($subtotal)
        ->and($result->discount)->toBe($discount)
        ->and($result->shipping)->toBe($shipping)
        ->and($result->taxTotal)->toBe($tax)
        ->and($result->total)->toBe($total)
        ->and($result->currency)->toBe('EUR');
});

it('handles rounding correctly with odd cent amounts', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    // Create 3 lines with odd prices
    $prices = [3333, 3333, 3334];
    foreach ($prices as $price) {
        $product = Product::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'title' => "Product $price",
            'handle' => "product-$price-".rand(1000, 9999),
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price_amount' => $price,
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
        CartLine::create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price,
            'line_discount_amount' => 0,
            'line_total_amount' => $price,
        ]);
    }

    Discount::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => 'TEST10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'TEST10',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // Total discount should be exactly 10% of subtotal
    $subtotal = 10000;
    expect($result->subtotal)->toBe($subtotal)
        ->and($result->discount)->toBe(1000);
});

it('produces identical results for identical inputs', function () {
    $ctx = createPricingContext(['price' => 2500, 'quantity' => 2]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result1 = $engine->calculate($checkout);
    $result2 = $engine->calculate($checkout);

    expect($result1->toArray())->toBe($result2->toArray());
});

it('handles prices-include-tax correctly', function () {
    $ctx = createPricingContext(['price' => 11900, 'quantity' => 1]);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->subtotal)->toBe(11900);
});
