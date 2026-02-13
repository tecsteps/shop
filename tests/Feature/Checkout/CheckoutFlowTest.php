<?php

use App\Enums\CheckoutStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;

function createCheckoutFlowContext(): array
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
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::factory()->for($store)->create();
    $line = CartLine::factory()->create([
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
        'line' => $line,
        'zone' => $zone,
        'rate' => $rate,
    ]);
}

it('creates a checkout from a cart', function () {
    $ctx = createCheckoutFlowContext();
    $cart = $ctx['cart'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($cart->id)
        ->and($checkout->store_id)->toBe($cart->store_id);
});

it('completes full checkout happy path', function () {
    $ctx = createCheckoutFlowContext();
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);

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

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);

    $checkout = $service->setShippingMethod($checkout, $rate->id);
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);

    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');
    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected);

    $result = $service->completeCheckout($checkout);
    expect($result->status)->toBe(CheckoutStatus::Completed);
});

it('rejects checkout for empty cart', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $cart = Cart::factory()->for($store)->create();

    $service = app(CheckoutService::class);

    expect(fn () => $service->createCheckout($cart))
        ->toThrow(\InvalidArgumentException::class);
});

it('expires checkout after timeout', function () {
    $ctx = createCheckoutFlowContext();
    $cart = $ctx['cart'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);

    $service->expireCheckout($checkout);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired);
});

it('prevents duplicate checkouts from same cart', function () {
    $ctx = createCheckoutFlowContext();
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);

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
    $service->completeCheckout($checkout);

    expect(fn () => $service->createCheckout($cart))
        ->toThrow(\InvalidArgumentException::class);
});
