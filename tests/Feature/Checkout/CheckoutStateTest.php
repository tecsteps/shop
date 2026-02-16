<?php

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->checkoutService = app(CheckoutService::class);
});

it('transitions from started to addressed with valid address', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 2500, 'subtotal' => 2500, 'total' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Anytown',
            'country_code' => 'US',
            'zip' => '12345',
        ],
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
});

it('transitions from addressed to shipping_selected', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 2500, 'subtotal' => 2500, 'total' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => ['country_code' => 'US', 'city' => 'NYC'],
    ]);

    $zone = ShippingZone::factory()->for($this->ctx['store'])->create(['countries_json' => ['US'], 'is_active' => true]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->create(['amount' => 499, 'is_active' => true]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_amount)->toBe(499);
});

it('rejects invalid state transitions', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 2500, 'subtotal' => 2500, 'total' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);

    // Cannot go straight to completed from started
    $this->checkoutService->completeCheckout($checkout);
})->throws(InvalidCheckoutTransitionException::class);
