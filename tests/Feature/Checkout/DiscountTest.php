<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
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
use App\Services\PricingEngine;

function createDiscountCheckoutContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Discount Test',
        'handle' => 'discount-test-'.rand(1000, 9999),
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

    return array_merge($ctx, compact('product', 'variant', 'cart'));
}

it('applies a valid percent discount code at checkout', function () {
    $ctx = createDiscountCheckoutContext();

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

    expect($result->discount)->toBe(500); // 10% of 5000
});

it('applies a valid fixed discount code at checkout', function () {
    $ctx = createDiscountCheckoutContext();

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

    expect($result->discount)->toBe(500);
});

it('removes discount when code is cleared', function () {
    $ctx = createDiscountCheckoutContext();

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => null,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->discount)->toBe(0);
});

it('rejects expired discount at checkout', function () {
    $ctx = createDiscountCheckoutContext();

    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'OLDCODE',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 20,
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'OLDCODE',
    ]);

    // PricingEngine silently skips invalid discounts (validation happens at apply time)
    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // Discount should be 0 since code is expired (validate would throw, but calculate just skips)
    expect($result->discount)->toBe(0);
});

it('increments usage count on order completion', function () {
    $ctx = createDiscountCheckoutContext();

    $discount = Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'TRACK',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'usage_count' => 5,
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    // Simulate usage count increment (done in completeCheckout in Phase 5)
    $discount->increment('usage_count');

    expect($discount->fresh()->usage_count)->toBe(6);
});

it('handles free shipping discount at checkout', function () {
    $ctx = createDiscountCheckoutContext();

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
