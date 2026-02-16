<?php

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->checkoutService = app(CheckoutService::class);
});

it('creates a checkout from a cart', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 2, 'unit_price' => 2500, 'subtotal' => 5000, 'total' => 5000]);

    $checkout = $this->checkoutService->createFromCart($cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($cart->id);
});

it('prevents duplicate orders from same checkout', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 2500, 'subtotal' => 2500, 'total' => 2500]);

    $checkout = $this->checkoutService->createFromCart($cart);

    // Move to completed
    $checkout->update(['status' => CheckoutStatus::Completed, 'completed_at' => now()]);

    // Calling completeCheckout on already completed should return same checkout
    $result = $this->checkoutService->completeCheckout($checkout);
    expect($result->id)->toBe($checkout->id);
});
