<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);
    $cart = Cart::factory()->for($store)->create();
    $this->checkout = Checkout::factory()->for($store)->for($cart)->create();
    $this->provider = new MockPaymentProvider;
});

it('captures the successful magic card', function () {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => '4242 4242 4242 4242']);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe(PaymentStatus::Captured)
        ->and($result->providerPaymentId)->toStartWith('mock_');
});

it('returns stable decline outcomes for magic cards', function (string $card, string $errorCode) {
    $result = $this->provider->charge($this->checkout, PaymentMethod::CreditCard, ['card_number' => $card]);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->errorCode)->toBe($errorCode);
})->with([
    ['4000000000000002', 'card_declined'],
    ['4000000000009995', 'insufficient_funds'],
]);

it('captures paypal and defers bank transfers', function () {
    $paypal = $this->provider->charge($this->checkout, PaymentMethod::Paypal, []);
    $bank = $this->provider->charge($this->checkout, PaymentMethod::BankTransfer, []);

    expect($paypal->status)->toBe(PaymentStatus::Captured)
        ->and($bank->status)->toBe(PaymentStatus::Pending);
});
