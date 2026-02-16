<?php

use App\Contracts\PaymentProvider;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('processes credit card payment and creates order as paid', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 100]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 2500, 'subtotal' => 2500, 'total' => 2500]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payments)->toHaveCount(1);
});

it('processes bank transfer and creates order as pending', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 100]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 2500, 'subtotal' => 2500, 'total' => 2500]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'bank_transfer',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);

    expect($order->financial_status)->toBe(FinancialStatus::Pending);
});

it('resolves MockPaymentProvider from container', function () {
    $provider = app(PaymentProvider::class);
    expect($provider)->toBeInstanceOf(MockPaymentProvider::class);
});

it('creates a payment record with correct method', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 100]);
    CartLine::factory()->for($cart)->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 2500, 'subtotal' => 2500, 'total' => 2500]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);
    $payment = $order->payments->first();

    expect($payment->method->value)->toBe('credit_card')
        ->and($payment->provider)->toBe('mock');
});
