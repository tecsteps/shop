<?php

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Payment;
use App\Models\Store;
use App\Services\Payment\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->provider = new MockPaymentProvider;
});

function createTestCheckout(Store $store, string $paymentMethod): Checkout
{
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    return Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => $paymentMethod,
        'email' => 'test@example.com',
        'totals_json' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 500, 'tax_total' => 440, 'total' => 5940, 'currency' => 'USD'],
    ]);
}

test('credit card with success magic number returns captured', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::CreditCard->value);

    $result = $this->provider->charge($checkout, ['card_number' => '4242424242424242']);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
    expect($result->referenceId)->toStartWith('mock_');
    expect($result->errorCode)->toBeNull();
});

test('credit card with spaces in number returns captured', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::CreditCard->value);

    $result = $this->provider->charge($checkout, ['card_number' => '4242 4242 4242 4242']);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});

test('credit card with decline magic number fails', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::CreditCard->value);

    $result = $this->provider->charge($checkout, ['card_number' => '4000000000000002']);

    expect($result->success)->toBeFalse();
    expect($result->status)->toBe('failed');
    expect($result->errorCode)->toBe('card_declined');
    expect($result->errorMessage)->toBe('Your card was declined.');
});

test('credit card with insufficient funds magic number fails', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::CreditCard->value);

    $result = $this->provider->charge($checkout, ['card_number' => '4000000000009995']);

    expect($result->success)->toBeFalse();
    expect($result->status)->toBe('failed');
    expect($result->errorCode)->toBe('insufficient_funds');
    expect($result->errorMessage)->toBe('Your card has insufficient funds.');
});

test('credit card with unknown number succeeds', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::CreditCard->value);

    $result = $this->provider->charge($checkout, ['card_number' => '5555555555554444']);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});

test('paypal payment always succeeds with captured status', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::Paypal->value);

    $result = $this->provider->charge($checkout, []);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
    expect($result->referenceId)->toStartWith('mock_');
});

test('bank transfer returns pending status', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::BankTransfer->value);

    $result = $this->provider->charge($checkout, []);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('pending');
    expect($result->referenceId)->toStartWith('mock_');
});

test('refund always succeeds', function () {
    $payment = Payment::factory()->create([
        'order_id' => \App\Models\Order::factory()->create(['store_id' => $this->store->id])->id,
        'amount' => 5940,
    ]);

    $result = $this->provider->refund($payment, 2000);

    expect($result->success)->toBeTrue();
    expect($result->providerRefundId)->toStartWith('mock_refund_');
    expect($result->status)->toBe('processed');
});

test('each charge generates unique reference ids', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::CreditCard->value);

    $result1 = $this->provider->charge($checkout, ['card_number' => '4242424242424242']);
    $result2 = $this->provider->charge($checkout, ['card_number' => '4242424242424242']);

    expect($result1->referenceId)->not->toBe($result2->referenceId);
});

test('credit card with empty card number succeeds as unknown', function () {
    $checkout = createTestCheckout($this->store, PaymentMethod::CreditCard->value);

    $result = $this->provider->charge($checkout, ['card_number' => '']);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});
