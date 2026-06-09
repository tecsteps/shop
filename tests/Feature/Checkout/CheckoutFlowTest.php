<?php

use App\Enums\CheckoutStatus;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

it('creates a checkout from a cart', function () {
    $cart = Cart::factory()->for($this->store)->create();
    CartLine::factory()->for($cart)->priced(2500, 1)->create([
        'variant_id' => createPurchasableVariant($this->store)->getKey(),
    ]);
    CartLine::factory()->for($cart)->priced(3500, 1)->create([
        'variant_id' => createPurchasableVariant($this->store, 3500)->getKey(),
    ]);

    $checkout = $this->checkoutService->createFromCart($cart);

    $this->assertDatabaseHas('checkouts', [
        'id' => $checkout->getKey(),
        'cart_id' => $cart->getKey(),
        'store_id' => $this->store->getKey(),
        'status' => 'started',
    ]);
});

it('completes full checkout happy path')->todo('Phase 5: order creation via mock PSP');

it('rejects checkout for empty cart', function () {
    $cart = Cart::factory()->for($this->store)->create();

    $this->checkoutService->createFromCart($cart);
})->throws(ValidationException::class);

it('expires checkout after timeout', function () {
    $variant = createPurchasableVariant($this->store, 2500, 10);
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();

    $cartService = app(CartService::class);
    $cart = $cartService->create($this->store);
    $cartService->addLine($cart, $variant->getKey(), 3);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $zone->rates()->first()->getKey());
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    expect($variant->inventoryItem->refresh()->quantity_reserved)->toBe(3);

    $checkout->forceFill(['expires_at' => now()->subHour()])->save();

    (new ExpireAbandonedCheckouts)->handle($this->checkoutService);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Expired);
    expect($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});

it('prevents duplicate orders from same checkout')->todo('Phase 5: order creation via mock PSP');
