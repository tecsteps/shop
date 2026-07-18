<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('handles magic credit card outcomes', function (string $card, bool $success, PaymentStatus $status, ?string $error) {
    $result = app(MockPaymentProvider::class)->charge(
        Checkout::factory()->create(),
        PaymentMethod::CreditCard,
        ['card_number' => $card],
    );

    expect($result->success)->toBe($success)
        ->and($result->status)->toBe($status)
        ->and($result->errorCode)->toBe($error);
})->with([
    'success' => ['4242424242424242', true, PaymentStatus::Captured, null],
    'declined' => ['4000000000000002', false, PaymentStatus::Failed, 'card_declined'],
    'insufficient funds' => ['4000000000009995', false, PaymentStatus::Failed, 'insufficient_funds'],
]);

it('returns pending for bank transfers', function () {
    $result = app(MockPaymentProvider::class)->charge(
        Checkout::factory()->create(),
        PaymentMethod::BankTransfer,
        [],
    );

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Pending);
});
