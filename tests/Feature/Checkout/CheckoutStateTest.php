<?php

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidShippingRateException;
use App\Models\Checkout;
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

/**
 * Create a checkout in the "started" state from a single-line cart.
 */
function startedCheckout($test, array $variantAttributes = [], int $quantity = 1): Checkout
{
    $variant = createPurchasableVariant($test->store, 2500, 100, $variantAttributes);

    $cartService = app(CartService::class);
    $cart = $cartService->create($test->store);
    $cartService->addLine($cart, $variant->getKey(), $quantity);

    return $test->checkoutService->createFromCart($cart);
}

/**
 * Create a DE shipping zone with one flat rate for the store.
 */
function makeGermanZone($test, int $amount = 499): ShippingRate
{
    $zone = ShippingZone::factory()->for($test->store)->create(['countries_json' => ['DE']]);

    return ShippingRate::factory()->for($zone, 'zone')->flatAmount($amount)->create();
}

it('transitions from started to addressed with valid address', function () {
    $checkout = startedCheckout($this);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
    expect($checkout->email)->toBe('shopper@example.test');
    expect($checkout->billing_address_json)->toBe($checkout->shipping_address_json);
});

it('rejects address transition with missing required fields', function () {
    $checkout = startedCheckout($this);

    try {
        $this->checkoutService->setAddress($checkout, [
            'email' => 'shopper@example.test',
            'shipping_address' => collect(validShippingAddress())->except('city')->all(),
        ]);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('shipping_address.city');
    }

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Started);
});

it('transitions from addressed to shipping_selected', function () {
    $checkout = startedCheckout($this);
    $rate = makeGermanZone($this);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->getKey());

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($checkout->shipping_method_id)->toBe($rate->getKey());
});

it('rejects shipping selection with rate from wrong zone', function () {
    $checkout = startedCheckout($this);

    $usZone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['US']]);
    $usRate = ShippingRate::factory()->for($usZone, 'zone')->create();

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    $this->checkoutService->setShippingMethod($checkout, $usRate->getKey());
})->throws(InvalidShippingRateException::class);

it('skips shipping selection when no items require shipping', function () {
    $checkout = startedCheckout($this, ['requires_shipping' => false]);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($checkout->shipping_method_id)->toBeNull();
    expect($checkout->totals_json['shipping'])->toBe(0);
});

it('transitions from shipping_selected to payment_selected', function () {
    $checkout = startedCheckout($this, quantity: 2);
    $rate = makeGermanZone($this);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->getKey());

    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected);
    expect($checkout->payment_method)->toBe('credit_card');
    expect($checkout->expires_at)->not->toBeNull();

    $reserved = $checkout->cart->lines()->first()->variant->inventoryItem->quantity_reserved;
    expect($reserved)->toBe(2);
});

it('transitions from payment_selected to completed')->todo('Phase 5: order creation via mock PSP');

it('rejects invalid state transitions', function () {
    $checkout = startedCheckout($this);

    $this->checkoutService->completeCheckout($checkout);
})->throws(InvalidCheckoutTransitionException::class);

it('recalculates pricing on address change', function () {
    $checkout = startedCheckout($this);
    $rate = makeGermanZone($this);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->getKey());

    expect($checkout->totals_json['shipping'])->toBe(499);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(['country_code' => 'FR', 'city' => 'Paris', 'postal_code' => '75001']),
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
    expect($checkout->shipping_method_id)->toBeNull();
    expect($checkout->totals_json['shipping'])->toBe(0);
});
