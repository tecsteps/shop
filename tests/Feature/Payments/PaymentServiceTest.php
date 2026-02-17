<?php

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Store;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->provider = app(PaymentProvider::class);
});

it('binds mock payment provider via interface', function () {
    expect($this->provider)->toBeInstanceOf(MockPaymentProvider::class);
});

it('generates unique provider payment ids', function () {
    $checkout = createCheckoutWithCart($this->store);

    $result1 = $this->provider->charge($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);
    $result2 = $this->provider->charge($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($result1->providerPaymentId)->not->toBe($result2->providerPaymentId);
});

it('processes refund successfully', function () {
    $payment = Payment::factory()->create([
        'amount' => 5000,
    ]);

    $result = $this->provider->refund($payment, 2500);

    expect($result->success)->toBeTrue()
        ->and($result->providerRefundId)->toStartWith('mock_refund_');
});

it('handles credit card with spaces in number', function () {
    $checkout = createCheckoutWithCart($this->store);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242 4242 4242 4242',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

it('handles missing card number gracefully', function () {
    $checkout = createCheckoutWithCart($this->store);

    $result = $this->provider->charge($checkout, PaymentMethod::CreditCard, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured');
});

function createCheckoutWithCart(\App\Models\Store $store): \App\Models\Checkout
{
    $cart = \App\Models\Cart::factory()->create(['store_id' => $store->id]);

    return \App\Models\Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => \App\Enums\CheckoutStatus::PaymentSelected,
        'totals_json' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 5000, 'currency' => 'EUR'],
    ]);
}
