<?php

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Services\Payments\MockPaymentProvider;

it('charges credit card with success card number', function () {
    $provider = app(PaymentProvider::class);
    $checkout = Checkout::factory()->create();

    $result = $provider->charge($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});

it('declines credit card with decline card number', function () {
    $provider = app(PaymentProvider::class);
    $result = $provider->charge(Checkout::factory()->create(), PaymentMethod::CreditCard, ['card_number' => '4000000000000002']);

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for that card number', function () {
    $provider = app(PaymentProvider::class);
    $result = $provider->charge(Checkout::factory()->create(), PaymentMethod::CreditCard, ['card_number' => '4000000000009995']);

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('insufficient_funds');
});

it('charges PayPal successfully', function () {
    $provider = app(PaymentProvider::class);
    $result = $provider->charge(Checkout::factory()->create(), PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});

it('creates pending payment for bank transfer', function () {
    $provider = app(PaymentProvider::class);
    $result = $provider->charge(Checkout::factory()->create(), PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('pending');
});

it('generates mock reference ID', function () {
    $provider = app(PaymentProvider::class);
    $result = $provider->charge(Checkout::factory()->create(), PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($result->referenceId)->toStartWith('mock_');
});
