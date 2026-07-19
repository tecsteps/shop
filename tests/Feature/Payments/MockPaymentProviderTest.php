<?php

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Payment;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->provider = new MockPaymentProvider;
    $this->checkout = new Checkout;
});

test('charges credit card with success card number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->errorCode)->toBeNull();
});

test('strips spaces from card numbers before matching', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4000 0000 0000 0002']);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('card_declined');
});

test('declines credit card with decline card number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4000000000000002']);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('card_declined')
        ->and($result->errorMessage)->toBe('Your card was declined.');
});

test('returns insufficient funds for that card number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4000000000009995']);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('insufficient_funds')
        ->and($result->errorMessage)->toBe('Your card has insufficient funds.');
});

test('charges any other valid-looking card number successfully', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4111111111111111']);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

test('charges PayPal successfully', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

test('creates pending payment for bank transfer', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('pending');
});

test('generates mock reference id', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::Paypal, []);

    expect($result->referenceId)->toStartWith('mock_');
});

test('refund always succeeds with a mock refund id', function () {
    $result = $this->provider->refund(new Payment, 5000);

    expect($result->success)->toBeTrue()
        ->and($result->providerRefundId)->toStartWith('mock_refund_')
        ->and($result->status)->toBe('processed');
});
