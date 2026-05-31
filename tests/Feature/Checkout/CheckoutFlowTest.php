<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CheckoutService::class);
});

it('creates a checkout from a cart', function () {
    $cart = cartWithLines([[2500, 1], [1500, 2]], ['requires_shipping' => false]);

    $checkout = $this->service->startFromCart($cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($cart->id);
});

it('completes full checkout happy path', function () {
    $checkout = startCheckout(['price' => 5000]); // physical product (requires shipping)
    $this->service->setAddress($checkout, germanAddressData());

    $zone = App\Models\ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = App\Models\ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $this->service->setShippingMethod($checkout->fresh(), $rate->id);
    $this->service->selectPaymentMethod($checkout->fresh(), PaymentMethod::CreditCard);

    $order = $this->service->completeCheckout($checkout->fresh(), ['card_number' => '4242424242424242']);

    $item = $checkout->cart->lines->first()->variant->inventoryItem->fresh();

    expect($order->status->value)->toBe('paid')
        ->and($order->fulfillment_status->value)->toBe('unfulfilled')
        ->and($checkout->cart->fresh()->status)->toBe(CartStatus::Converted)
        ->and($item->quantity_on_hand)->toBe(99)
        ->and($item->quantity_reserved)->toBe(0);
});

it('rejects checkout for empty cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect(fn () => $this->service->startFromCart($cart))->toThrow(ValidationException::class);
});

it('expires checkout after timeout', function () {
    $checkout = startCheckout(['requires_shipping' => false]);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), null);
    $this->service->selectPaymentMethod($checkout->fresh(), PaymentMethod::CreditCard);

    $item = $checkout->cart->lines->first()->variant->inventoryItem->fresh();
    expect($item->quantity_reserved)->toBe(1);

    // Move expiry into the past, then run the scheduled job.
    Checkout::withoutGlobalScopes()->whereKey($checkout->id)->update(['expires_at' => Carbon::now()->subHour()]);

    app(ExpireAbandonedCheckouts::class)->handle($this->service);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($item->fresh()->quantity_reserved)->toBe(0);
});

it('prevents duplicate orders from same checkout', function () {
    $checkout = startCheckout(['requires_shipping' => false]);
    $this->service->setAddress($checkout, germanAddressData());
    $this->service->setShippingMethod($checkout->fresh(), null);
    $this->service->selectPaymentMethod($checkout->fresh(), PaymentMethod::CreditCard);

    $first = $this->service->completeCheckout($checkout->fresh(), ['card_number' => '4242424242424242']);
    $second = $this->service->completeCheckout($checkout->fresh(), ['card_number' => '4242424242424242']);

    expect($second->id)->toBe($first->id)
        ->and(App\Models\Order::withoutGlobalScopes()->where('checkout_id', $checkout->id)->count())->toBe(1);
});
