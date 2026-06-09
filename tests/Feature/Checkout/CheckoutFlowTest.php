<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Order;
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

it('completes full checkout happy path', function () {
    $variant = createPurchasableVariant($this->store, 2500, 10);
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();

    $cartService = app(CartService::class);
    $cart = $cartService->create($this->store);
    $cartService->addLine($cart, $variant->getKey(), 2);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $zone->rates()->first()->getKey());
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $this->assertDatabaseHas('orders', [
        'id' => $order->getKey(),
        'store_id' => $this->store->getKey(),
        'status' => 'paid',
        'financial_status' => 'paid',
        'total_amount' => 5499,
    ]);
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Completed);
    expect($cart->refresh()->status)->toBe(CartStatus::Converted);

    $item = $variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(8);
    expect($item->quantity_reserved)->toBe(0);
});

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

it('prevents duplicate orders from same checkout', function () {
    $checkout = createPaymentSelectedCheckout($this->store);

    $first = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);
    $second = $this->checkoutService->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);

    expect($second->getKey())->toBe($first->getKey());
    expect(Order::query()->count())->toBe(1);
});
