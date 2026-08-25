<?php

use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ProductService;

function makeStartedCheckout(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $product = app(ProductService::class)->create($store, ['title' => 'Widget', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $product->variants()->first()->id, 1);
    $checkout = app(CheckoutService::class)->create($cart, 'x@example.com');

    return ['checkout' => $checkout, 'store' => $store, 'rate' => $rate];
}

it('transitions from started to addressed with valid address', function () {
    ['checkout' => $checkout] = makeStartedCheckout();

    app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'x@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);

    expect($checkout->fresh()->status)->toBe('addressed');
});

it('transitions from addressed to shipping_selected', function () {
    ['checkout' => $checkout, 'rate' => $rate] = makeStartedCheckout();
    app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'x@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);

    app(CheckoutService::class)->setShippingMethod($checkout, $rate->id);

    expect($checkout->fresh()->status)->toBe('shipping_selected');
});

it('transitions from shipping_selected to payment_selected', function () {
    ['checkout' => $checkout, 'rate' => $rate] = makeStartedCheckout();
    app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'x@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);
    app(CheckoutService::class)->setShippingMethod($checkout, $rate->id);

    app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->fresh()->status)->toBe('payment_selected');
    expect($checkout->fresh()->expires_at)->not->toBeNull();
});

it('rejects invalid state transitions', function () {
    ['checkout' => $checkout] = makeStartedCheckout();

    expect(fn () => app(CheckoutService::class)->completeCheckout($checkout, []))
        ->toThrow(InvalidCheckoutTransitionException::class);
});
