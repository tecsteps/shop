<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Payment;
use App\Services\Payments\MockPaymentProvider;

test('mock provider captures successful card and paypal payments', function () {
    $provider = new MockPaymentProvider;
    $checkout = new Checkout;

    $card = $provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242 4242 4242 4242',
    ]);
    $paypal = $provider->charge($checkout, PaymentMethod::Paypal, []);

    expect($card->success)->toBeTrue()
        ->and($card->status)->toBe(PaymentStatus::Captured)
        ->and($card->reference)->toStartWith('mock_')
        ->and($paypal->success)->toBeTrue()
        ->and($paypal->status)->toBe(PaymentStatus::Captured);
});

test('mock provider returns expected card decline codes', function (string $cardNumber, string $errorCode, string $message) {
    $result = (new MockPaymentProvider)->charge(new Checkout, PaymentMethod::CreditCard, [
        'card_number' => $cardNumber,
    ]);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->errorCode)->toBe($errorCode)
        ->and($result->message)->toBe($message);
})->with([
    'declined' => ['4000000000000002', 'card_declined', 'Your card was declined.'],
    'insufficient funds' => ['4000000000009995', 'insufficient_funds', 'Your card has insufficient funds.'],
]);

test('mock provider creates pending bank transfer charges and processed refunds', function () {
    $provider = new MockPaymentProvider;
    $charge = $provider->charge(new Checkout, PaymentMethod::BankTransfer, []);
    $refund = $provider->refund(new Payment(['amount' => 1000]), 500);

    expect($charge->success)->toBeTrue()
        ->and($charge->status)->toBe(PaymentStatus::Pending)
        ->and($refund->success)->toBeTrue();
});
