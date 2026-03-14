<?php

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Services\Payments\MockPaymentProvider;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->provider = new MockPaymentProvider;
});

it('charges credit card with success number as captured', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
    expect($result->providerPaymentId)->toStartWith('mock_');
});

it('declines with decline card number', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000000002',
    ]);

    expect($result->success)->toBeFalse();
    expect($result->status)->toBe('failed');
    expect($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for insufficient funds card number', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4000000000009995',
    ]);

    expect($result->success)->toBeFalse();
    expect($result->errorCode)->toBe('insufficient_funds');
});

it('charges PayPal as captured', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::Paypal, []);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('captured');
});

it('creates pending payment for bank transfer', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::BankTransfer, []);

    expect($result->success)->toBeTrue();
    expect($result->status)->toBe('pending');
});

it('generates mock reference ID starting with mock_', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($result->providerPaymentId)->toStartWith('mock_');
    expect(strlen($result->providerPaymentId))->toBeGreaterThan(5);
});
