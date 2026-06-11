<?php

use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->baseUrl = 'http://'.$this->context['domain']->hostname.'/api/storefront/v1';
});

/**
 * A cart containing one purchasable variant for the current test store.
 */
function checkoutApiCart(): \App\Models\Cart
{
    $variant = createPurchasableVariant(test()->store, 2500);
    $cart = app(CartService::class)->create(test()->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    return $cart;
}

/**
 * A German shipping zone with a flat 499 rate for the current test store.
 */
function checkoutApiShippingRate(): ShippingRate
{
    $zone = ShippingZone::factory()->for(test()->store)->create(['countries_json' => ['DE']]);

    return ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();
}

it('creates a checkout from a cart', function () {
    $cart = checkoutApiCart();

    $this->postJson("{$this->baseUrl}/checkouts", [
        'cart_id' => $cart->getKey(),
        'email' => 'shopper@example.test',
    ])
        ->assertCreated()
        ->assertJsonPath('status', 'started')
        ->assertJsonPath('cart_id', $cart->getKey())
        ->assertJsonPath('email', 'shopper@example.test');
});

it('sets checkout address', function () {
    $cart = checkoutApiCart();
    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout->forceFill(['email' => 'shopper@example.test'])->save();

    $this->putJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}/address", [
        'shipping_address' => validShippingAddress(),
    ])
        ->assertOk()
        ->assertJsonPath('status', 'addressed')
        ->assertJsonPath('shipping_address_json.city', 'Berlin');
});

it('selects a shipping method', function () {
    $cart = checkoutApiCart();
    $rate = checkoutApiShippingRate();
    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->forceFill(['email' => 'shopper@example.test'])->save();
    $checkout = $checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    $this->putJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}/shipping-method", [
        'shipping_method_id' => $rate->getKey(),
    ])
        ->assertOk()
        ->assertJsonPath('status', 'shipping_selected')
        ->assertJsonPath('shipping_method_id', $rate->getKey())
        ->assertJsonPath('available_shipping_methods.0.id', $rate->getKey())
        ->assertJsonPath('totals.shipping', 499);
});

it('applies a discount code', function () {
    Discount::factory()->for($this->store)->create([
        'code' => 'WELCOME10',
        'value_amount' => 10,
    ]);

    $cart = checkoutApiCart();
    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout->forceFill(['email' => 'shopper@example.test'])->save();

    $this->postJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}/apply-discount", [
        'code' => 'WELCOME10',
    ])
        ->assertOk()
        ->assertJsonPath('discount_code', 'WELCOME10')
        ->assertJsonPath('totals.discount', 500);
});

it('retrieves checkout with totals', function () {
    $cart = checkoutApiCart();
    $checkout = app(CheckoutService::class)->createFromCart($cart);

    $this->getJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}")
        ->assertOk()
        ->assertJsonStructure(['id', 'status', 'lines', 'totals' => ['subtotal', 'discount', 'shipping', 'tax', 'total', 'currency']])
        ->assertJsonPath('totals.subtotal', 5000);
});

it('selects a payment method', function () {
    $checkout = shippingSelectedCheckoutForApi();

    $this->putJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}/payment-method", [
        'payment_method' => 'credit_card',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'payment_selected')
        ->assertJsonPath('payment_method', 'credit_card');
});

it('completes checkout with credit card payment', function () {
    $checkout = createPaymentSelectedCheckout($this->store);

    $this->postJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Erika Mustermann',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('order.status', 'paid')
        ->assertJsonPath('order.financial_status', 'paid');

    $this->assertDatabaseHas('orders', [
        'checkout_id' => $checkout->getKey(),
        'status' => 'paid',
    ]);
});

it('rejects payment with declined card', function () {
    $checkout = createPaymentSelectedCheckout($this->store);

    $this->postJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Erika Mustermann',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'card_declined');

    $this->assertDatabaseMissing('orders', ['checkout_id' => $checkout->getKey()]);
});

it('validates required address fields', function () {
    $cart = checkoutApiCart();
    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout->forceFill(['email' => 'shopper@example.test'])->save();

    $this->putJson("{$this->baseUrl}/checkouts/{$checkout->getKey()}/address", [
        'shipping_address' => validShippingAddress(['city' => '']),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('shipping_address.city');
});

/**
 * Drive a checkout to the shipping_selected state for the current test store.
 */
function shippingSelectedCheckoutForApi(): \App\Models\Checkout
{
    $cart = checkoutApiCart();
    $rate = checkoutApiShippingRate();
    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout->forceFill(['email' => 'shopper@example.test'])->save();
    $checkout = $checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    return $checkoutService->setShippingMethod($checkout, $rate->getKey());
}
