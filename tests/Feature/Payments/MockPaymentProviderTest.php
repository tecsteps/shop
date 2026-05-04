<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\Services\Payments\MockPaymentProvider;

test('mock provider handles credit card magic numbers', function (string $number, bool $success, PaymentStatus $status, ?string $errorCode) {
    $result = app(MockPaymentProvider::class)->charge(
        new Checkout,
        PaymentMethod::CreditCard,
        ['card_number' => $number],
    );

    expect($result->success)->toBe($success)
        ->and($result->status)->toBe($status)
        ->and($result->errorCode)->toBe($errorCode);

    if ($success) {
        expect($result->referenceId)->toStartWith('mock_payment_');
    }
})->with([
    'captured card' => ['4242 4242 4242 4242', true, PaymentStatus::Captured, null],
    'declined card' => ['4000 0000 0000 0002', false, PaymentStatus::Failed, 'card_declined'],
    'insufficient funds' => ['4000 0000 0000 9995', false, PaymentStatus::Failed, 'insufficient_funds'],
    'other card' => ['4111 1111 1111 1111', true, PaymentStatus::Captured, null],
]);

test('mock provider captures paypal and leaves bank transfer pending', function () {
    $provider = app(MockPaymentProvider::class);

    $paypal = $provider->charge(new Checkout, PaymentMethod::Paypal);
    $bankTransfer = $provider->charge(new Checkout, PaymentMethod::BankTransfer);

    expect($paypal->success)->toBeTrue()
        ->and($paypal->status)->toBe(PaymentStatus::Captured)
        ->and($paypal->referenceId)->toStartWith('mock_payment_')
        ->and($bankTransfer->success)->toBeTrue()
        ->and($bankTransfer->status)->toBe(PaymentStatus::Pending)
        ->and($bankTransfer->referenceId)->toStartWith('mock_bank_');
});

test('mock provider processes valid refunds and rejects invalid amounts', function () {
    $provider = app(MockPaymentProvider::class);

    $processed = $provider->refund(new Payment, 500);
    $failed = $provider->refund(new Payment, 0);

    expect($processed->success)->toBeTrue()
        ->and($processed->status)->toBe(RefundStatus::Processed)
        ->and($processed->referenceId)->toStartWith('mock_refund_')
        ->and($failed->success)->toBeFalse()
        ->and($failed->status)->toBe(RefundStatus::Failed)
        ->and($failed->errorCode)->toBe('invalid_refund_amount');
});
