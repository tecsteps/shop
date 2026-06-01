<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Services\Payment\MockPaymentProvider;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->provider = new MockPaymentProvider;
    $this->checkout = Checkout::factory()->create(['store_id' => $this->context['store']->id]);
});

it('charges credit card with success card number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Captured);
});

it('declines credit card with decline card number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4000000000000002']);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for that card number', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4000000000009995']);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('insufficient_funds');
});

it('charges PayPal successfully', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Captured);
});

it('creates pending payment for bank transfer', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Pending);
});

it('generates mock reference ID', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($result->referenceId)->toStartWith('mock_');
});

it('accepts spaced magic card numbers', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4000 0000 0000 0002']);

    expect($result->errorCode)->toBe('card_declined');
});
