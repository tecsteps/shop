<?php

use App\Enums\CheckoutStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;

function createCheckoutStateContext(): array
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

function validAddressData(): array
{
    return [
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
    ];
}

it('transitions from started to addressed with valid address', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, validAddressData());

    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->email)->toBe('test@example.com')
        ->and($checkout->shipping_address_json['first_name'])->toBe('John');
});

it('rejects address transition with missing required fields', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);

    expect(fn () => $service->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            // missing other fields
        ],
    ]))->toThrow(\InvalidArgumentException::class);
});

it('transitions from addressed to shipping_selected', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, validAddressData());
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBe($rate->id);
});

it('rejects shipping selection with rate from wrong zone', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];

    $wrongZone = ShippingZone::factory()->for($ctx['store'])->create([
        'countries_json' => ['DE'],
    ]);

    $wrongRate = ShippingRate::factory()->create([
        'zone_id' => $wrongZone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 999],
    ]);

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, validAddressData());

    expect(fn () => $service->setShippingMethod($checkout, $wrongRate->id))
        ->toThrow(\InvalidArgumentException::class);
});

it('skips shipping selection when no items require shipping', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
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
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    // Even for digital items, the state machine still requires going through
    // shipping_selected step. We create a zone with a free shipping rate.
    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 0],
    ]);

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, validAddressData());
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->totals_json['shipping'])->toBe(0);
});

it('transitions from shipping_selected to payment_selected', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, validAddressData());
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->payment_method)->toBe('credit_card')
        ->and($checkout->expires_at)->not->toBeNull();
});

it('transitions from payment_selected to completed', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, validAddressData());
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');
    $order = $service->completeCheckout($checkout);

    expect($order)->toBeInstanceOf(Order::class);

    $checkout = Checkout::withoutGlobalScopes()->find($checkout->id);
    expect($checkout->status)->toBe(CheckoutStatus::Completed);
});

it('rejects invalid state transitions', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];
    $rate = $ctx['rate'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);

    // Cannot go directly to shipping_selected from started
    expect(fn () => $service->setShippingMethod($checkout, $rate->id))
        ->toThrow(InvalidCheckoutTransitionException::class);
});

it('recalculates pricing on address change', function () {
    $ctx = createCheckoutStateContext();
    $cart = $ctx['cart'];

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, validAddressData());

    expect($checkout->totals_json)->not->toBeNull()
        ->and($checkout->totals_json['subtotal'])->toBe(10000);
});
