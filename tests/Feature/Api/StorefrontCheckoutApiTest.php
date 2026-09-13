<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Illuminate\Support\Facades\Cache::flush();
});

function checkoutApiContext(): array
{
    ['store' => $store, 'domain' => $domain] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 2]]);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    return ['store' => $store, 'domain' => $domain, 'cart' => $cart, 'rate' => $rate];
}

function apiUrl($domain, string $path): string
{
    return CommerceTestHelper::storefrontUrl($domain, $path);
}

it('creates a checkout from a cart', function () {
    $ctx = checkoutApiContext();

    $response = $this->postJson(apiUrl($ctx['domain'], '/api/storefront/v1/checkouts'), [
        'cart_id' => $ctx['cart']->id,
        'email' => 'customer@example.com',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('status', 'started');
    $response->assertJsonPath('totals.subtotal', 5000);
});

it('sets checkout address', function () {
    $ctx = checkoutApiContext();
    $checkout = app(App\Services\CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    $response = $this->putJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/address"),
        ['shipping_address' => CommerceTestHelper::address()]
    );

    $response->assertOk();
    $response->assertJsonPath('status', 'addressed');
    expect($response->json('available_shipping_methods'))->toHaveCount(1);
});

it('selects a shipping method', function () {
    $ctx = checkoutApiContext();
    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());

    $response = $this->putJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/shipping-method"),
        ['shipping_method_id' => $ctx['rate']->id]
    );

    $response->assertOk();
    $response->assertJsonPath('status', 'shipping_selected');
    $response->assertJsonPath('totals.shipping', 499);
});

it('applies a discount code', function () {
    $ctx = checkoutApiContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ]);

    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);

    $response = $this->postJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/apply-discount"),
        ['code' => 'SAVE10']
    );

    $response->assertOk();
    $response->assertJsonPath('discount_code', 'SAVE10');
    $response->assertJsonPath('totals.discount', 500);
});

it('retrieves checkout with totals', function () {
    $ctx = checkoutApiContext();
    $checkout = app(App\Services\CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    $response = $this->getJson(apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}"));

    $response->assertOk();
    $response->assertJsonPath('totals.subtotal', 5000);
    $response->assertJsonPath('totals.total', 5000);
    expect($response->json('totals'))->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax', 'total', 'currency']);
});

it('selects a payment method', function () {
    $ctx = checkoutApiContext();
    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);

    $response = $this->putJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/payment-method"),
        ['payment_method' => 'credit_card']
    );

    $response->assertOk();
    $response->assertJsonPath('status', 'payment_selected');
    $response->assertJsonPath('payment_method', 'credit_card');
});

it('completes checkout with credit card payment', function () {
    $ctx = checkoutApiContext();
    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, App\Enums\PaymentMethod::CreditCard);

    $response = $this->postJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/pay"),
        [
            'payment_method' => 'credit_card',
            'card_number' => '4242424242424242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ]
    );

    $response->assertOk();
    $response->assertJsonPath('status', 'completed');
    $response->assertJsonPath('order.status', 'paid');
    $response->assertJsonPath('order.order_number', '#1001');
});

it('rejects payment with declined card', function () {
    $ctx = checkoutApiContext();
    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, App\Enums\PaymentMethod::CreditCard);

    $response = $this->postJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/pay"),
        [
            'payment_method' => 'credit_card',
            'card_number' => '4000000000000002',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ]
    );

    $response->assertUnprocessable();
    $response->assertJsonPath('error_code', 'card_declined');
    expect($checkout->refresh()->status)->toBe(App\Enums\CheckoutStatus::ShippingSelected);
});

it('validates required address fields', function () {
    $ctx = checkoutApiContext();
    $checkout = app(App\Services\CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    $address = CommerceTestHelper::address();
    unset($address['city'], $address['postal_code']);

    $response = $this->putJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/address"),
        ['shipping_address' => $address]
    );

    $response->assertUnprocessable();
});

it('returns discount error codes for invalid codes', function () {
    $ctx = checkoutApiContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'OLD',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ]);

    $checkout = app(App\Services\CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    $expired = $this->postJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/apply-discount"),
        ['code' => 'OLD']
    );
    $expired->assertStatus(400);
    $expired->assertJsonPath('error_code', 'discount_expired');

    $missing = $this->postJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/apply-discount"),
        ['code' => 'NOPE']
    );
    $missing->assertUnprocessable();
    $missing->assertJsonPath('error_code', 'discount_not_found');
});

it('processes bank transfer payment with instructions', function () {
    $ctx = checkoutApiContext();
    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, App\Enums\PaymentMethod::BankTransfer);

    $response = $this->postJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/pay"),
        ['payment_method' => 'bank_transfer']
    );

    $response->assertOk();
    $response->assertJsonPath('order.financial_status', 'pending');
    $response->assertJsonPath('bank_transfer_instructions.bank_name', 'Mock Bank AG');
    $response->assertJsonPath('bank_transfer_instructions.reference', '#1001');
});

it('rejects pay when checkout is not ready', function () {
    $ctx = checkoutApiContext();
    $checkout = app(App\Services\CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    $response = $this->postJson(
        apiUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/pay"),
        ['payment_method' => 'paypal']
    );

    $response->assertConflict();
});

it('retrieves an order with a valid token', function () {
    $ctx = checkoutApiContext();
    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, App\Enums\PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, App\Enums\PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $token = App\Support\OrderAccessToken::generate($order);
    $orderNumber = urlencode($order->order_number);

    $response = $this->getJson(apiUrl($ctx['domain'], "/api/storefront/v1/orders/{$orderNumber}?token={$token}"));

    $response->assertOk();
    $response->assertJsonPath('order_number', '#1001');
    expect($response->json('lines'))->toHaveCount(1);
});

it('rejects order lookup with invalid token', function () {
    $ctx = checkoutApiContext();
    $checkouts = app(App\Services\CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, App\Enums\PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, App\Enums\PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $orderNumber = urlencode($order->order_number);

    $this->getJson(apiUrl($ctx['domain'], "/api/storefront/v1/orders/{$orderNumber}?token=invalid"))
        ->assertUnauthorized();

    $this->getJson(apiUrl($ctx['domain'], "/api/storefront/v1/orders/{$orderNumber}"))
        ->assertUnauthorized();
});
