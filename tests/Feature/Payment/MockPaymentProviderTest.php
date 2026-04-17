<?php

use App\Models\Checkout;
use App\Models\Payment;
use App\Models\Store;
use App\Services\Payment\MockPaymentProvider;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->provider = new MockPaymentProvider;
});

it('charges credit card successfully with default card number', function () {
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id]);

    $result = $this->provider->charge($checkout, 'credit_card', [
        'card_number' => '4242424242424242',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->providerPaymentId)->toStartWith('mock_');
});

it('declines credit card with magic decline number', function () {
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id]);

    $result = $this->provider->charge($checkout, 'credit_card', [
        'card_number' => '4000000000000002',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->error)->toBe('card_declined');
});

it('returns insufficient funds for magic number', function () {
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id]);

    $result = $this->provider->charge($checkout, 'credit_card', [
        'card_number' => '4000000000009995',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('insufficient_funds');
});

it('accepts any other card number as success', function () {
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id]);

    $result = $this->provider->charge($checkout, 'credit_card', [
        'card_number' => '5555555555554444',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('always succeeds for paypal', function () {
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id]);

    $result = $this->provider->charge($checkout, 'paypal');

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('returns pending for bank transfer', function () {
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id]);

    $result = $this->provider->charge($checkout, 'bank_transfer');

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('pending');
});

it('processes refunds successfully', function () {
    $payment = Payment::factory()->create();

    $result = $this->provider->refund($payment, 1000);

    expect($result->success)->toBeTrue()
        ->and($result->providerRefundId)->toStartWith('mock_refund_');
});

it('handles card numbers with spaces', function () {
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id]);

    $result = $this->provider->charge($checkout, 'credit_card', [
        'card_number' => '4000 0000 0000 0002',
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('card_declined');
});
