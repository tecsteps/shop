<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\Services\Payments\MockPaymentProvider;
use App\ValueObjects\PaymentResult;
use App\ValueObjects\RefundResult;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->provider = new MockPaymentProvider;
});

it('charges a valid credit card successfully', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
    ]);

    $result = $this->provider->charge($checkout, ['card_number' => '4242424242424242']);

    expect($result)->toBeInstanceOf(PaymentResult::class)
        ->and($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Captured)
        ->and($result->providerPaymentId)->toStartWith('mock_')
        ->and($result->rawResponse['last4'])->toBe('4242');
});

it('declines a card with magic number 4000000000000002', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
    ]);

    $result = $this->provider->charge($checkout, ['card_number' => '4000000000000002']);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->errorCode)->toBe('card_declined');
});

it('returns insufficient funds for magic card 4000000000009995', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
    ]);

    $result = $this->provider->charge($checkout, ['card_number' => '4000000000009995']);

    expect($result->success)->toBeFalse()
        ->and($result->errorCode)->toBe('insufficient_funds');
});

it('charges PayPal successfully with captured status', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::Paypal,
    ]);

    $result = $this->provider->charge($checkout, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Captured)
        ->and($result->rawResponse['method'])->toBe('paypal');
});

it('charges bank transfer with pending status', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    $result = $this->provider->charge($checkout, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Pending)
        ->and($result->rawResponse['method'])->toBe('bank_transfer');
});

it('processes a refund successfully', function () {
    $payment = Payment::factory()->create();

    $result = $this->provider->refund($payment, 1000);

    expect($result)->toBeInstanceOf(RefundResult::class)
        ->and($result->success)->toBeTrue()
        ->and($result->providerRefundId)->toStartWith('mock_refund_');
});
