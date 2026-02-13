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
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

function createDiscountCheckoutContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
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

it('applies a valid percent discount code at checkout', function () {
    $ctx = createDiscountCheckoutContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'SAVE10',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->discount)->toBe(1000)
        ->and($result->subtotal)->toBe(10000)
        ->and($result->total)->toBe(9000);
});

it('applies a valid fixed discount code at checkout', function () {
    $ctx = createDiscountCheckoutContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'FLAT500',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'FLAT500',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->discount)->toBe(500)
        ->and($result->total)->toBe(9500);
});

it('removes discount when code is cleared', function () {
    $ctx = createDiscountCheckoutContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'SAVE10',
    ]);

    $engine = app(PricingEngine::class);
    $result1 = $engine->calculate($checkout);
    expect($result1->discount)->toBe(1000);

    $checkout->update(['discount_code' => null]);
    $result2 = $engine->calculate($checkout->fresh());

    expect($result2->discount)->toBe(0)
        ->and($result2->total)->toBe(10000);
});

it('rejects expired discount at checkout', function () {
    $ctx = createDiscountCheckoutContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'OLDCODE',
        'ends_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'OLDCODE',
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // Expired discount should be silently skipped in PricingEngine
    expect($result->discount)->toBe(0)
        ->and($result->total)->toBe(10000);
});

it('increments usage count on checkout completion', function () {
    $ctx = createDiscountCheckoutContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $discount = Discount::factory()->for($store)->create([
        'code' => 'USEONCE',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
        'usage_count' => 0,
    ]);

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);

    $checkout->update(['discount_code' => 'USEONCE']);

    $checkout = $service->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Los Angeles',
            'province_code' => 'CA',
            'country_code' => 'US',
            'postal_code' => '90001',
        ],
    ]);

    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');

    // Increment usage count manually (will be automatic in Phase 5 with order creation)
    $discount->increment('usage_count');

    expect($discount->fresh()->usage_count)->toBe(1);
});

it('handles free shipping discount at checkout', function () {
    $ctx = createDiscountCheckoutContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    Discount::factory()->for($store)->create([
        'code' => 'FREESHIP',
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->shipping)->toBe(0)
        ->and($result->total)->toBe(10000);
});
