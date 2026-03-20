<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Payment\MockPaymentProvider;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->provider = new MockPaymentProvider;

    $this->product = Product::factory()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);
});

it('captures a credit card payment with success card', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'payment_method' => PaymentMethod::CreditCard,
        'totals_json' => ['total_amount' => 5000],
    ]);

    $result = $this->provider->charge($checkout, ['card_number' => '4242424242424242']);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->providerPaymentId)->toStartWith('mock_')
        ->and($result->errorCode)->toBeNull();
});

it('declines a credit card with decline card number', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'payment_method' => PaymentMethod::CreditCard,
        'totals_json' => ['total_amount' => 5000],
    ]);

    $result = $this->provider->charge($checkout, ['card_number' => '4000000000000002']);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('card_declined');
});

it('declines a credit card with insufficient funds', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'payment_method' => PaymentMethod::CreditCard,
        'totals_json' => ['total_amount' => 5000],
    ]);

    $result = $this->provider->charge($checkout, ['card_number' => '4000000000009995']);

    expect($result->success)->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->errorCode)->toBe('insufficient_funds');
});

it('always succeeds for PayPal payments', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'payment_method' => PaymentMethod::Paypal,
        'totals_json' => ['total_amount' => 5000],
    ]);

    $result = $this->provider->charge($checkout, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('captured')
        ->and($result->providerPaymentId)->toStartWith('mock_');
});

it('returns pending status for bank transfer payments', function () {
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'payment_method' => PaymentMethod::BankTransfer,
        'totals_json' => ['total_amount' => 5000],
    ]);

    $result = $this->provider->charge($checkout, []);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('pending')
        ->and($result->providerPaymentId)->toStartWith('mock_')
        ->and($result->rawResponse)->toHaveKey('iban');
});

it('processes a refund successfully', function () {
    $order = \App\Models\Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);

    $payment = \App\Models\Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'status' => PaymentStatus::Captured,
    ]);

    $result = $this->provider->refund($payment, 2500);

    expect($result->success)->toBeTrue()
        ->and($result->status)->toBe('processed')
        ->and($result->providerRefundId)->toStartWith('mock_refund_');
});
