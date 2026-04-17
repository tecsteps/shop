<?php

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Services\Payments\MockPaymentProvider;

it('charges credit card with success card number', function () {
    $ctx = createStoreContext();

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $provider = new MockPaymentProvider;
    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->errorCode)->toBeNull();
});

it('declines credit card with decline card number', function () {
    $ctx = createStoreContext();

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $provider = new MockPaymentProvider;
    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000000002',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for that card number', function () {
    $ctx = createStoreContext();

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $provider = new MockPaymentProvider;
    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000009995',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('insufficient_funds');
});

it('charges PayPal successfully', function () {
    $ctx = createStoreContext();

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $provider = new MockPaymentProvider;
    $result = $provider->charge($checkout, PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('creates pending payment for bank transfer', function () {
    $ctx = createStoreContext();

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $provider = new MockPaymentProvider;
    $result = $provider->charge($checkout, PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('pending');
});

it('generates mock reference ID', function () {
    $ctx = createStoreContext();

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $provider = new MockPaymentProvider;
    $result = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->providerPaymentId)->toStartWith('mock_');
});
