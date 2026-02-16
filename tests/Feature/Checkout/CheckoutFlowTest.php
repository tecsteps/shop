<?php

use App\Enums\CheckoutStatus;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->checkoutService = app(CheckoutService::class);
});

it('creates a checkout from a cart', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store'], 2500, 2);

    $checkout = $this->checkoutService->createFromCart($cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($cart->id);
});

it('prevents duplicate orders from same checkout', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store']);

    $checkout = $this->checkoutService->createFromCart($cart);

    // Move to completed
    $checkout->update(['status' => CheckoutStatus::Completed, 'completed_at' => now()]);

    // Calling completeCheckout on already completed should return same checkout
    $result = $this->checkoutService->completeCheckout($checkout);
    expect($result->id)->toBe($checkout->id);
});
