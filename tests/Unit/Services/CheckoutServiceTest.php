<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;

it('places a credit-card order successfully', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        variantAttributes: ['requires_shipping' => false, 'price_amount' => 2500],
        inventoryAttributes: ['quantity_on_hand' => 10],
    );

    $cartService = app(CartService::class);
    $checkoutService = app(CheckoutService::class);

    $cart = $cartService->create($store);
    $cart = $cartService->addLine($cart, $variant->id, 2);

    $checkout = $checkoutService->createFromCart($cart, 'buyer@example.test');
    $checkout = $checkoutService->setAddress($checkout, null);
    $checkout = $checkoutService->setShippingMethod($checkout, null);
    $checkout = $checkoutService->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    $order = $checkoutService->placeOrder($checkout, [
        'payment_method' => PaymentMethod::CreditCard->value,
        'card_number' => '4242424242424242',
    ]);

    $payment = Payment::query()->where('order_id', $order->id)->first();
    $inventory = $variant->inventoryItem()->firstOrFail()->fresh();

    $checkout->refresh();
    $cart->refresh();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($payment?->status)->toBe(PaymentStatus::Captured)
        ->and($checkout->status)->toBe(CheckoutStatus::Completed)
        ->and($cart->status)->toBe(CartStatus::Converted)
        ->and($inventory->quantity_on_hand)->toBe(8)
        ->and($inventory->quantity_reserved)->toBe(0);
});

it('throws when credit-card payment is declined and releases reserved inventory', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        variantAttributes: ['requires_shipping' => false],
        inventoryAttributes: ['quantity_on_hand' => 10],
    );

    $cartService = app(CartService::class);
    $checkoutService = app(CheckoutService::class);

    $cart = $cartService->create($store);
    $cart = $cartService->addLine($cart, $variant->id, 2);

    $checkout = $checkoutService->createFromCart($cart, 'buyer@example.test');
    $checkout = $checkoutService->setAddress($checkout, null);
    $checkout = $checkoutService->setShippingMethod($checkout, null);
    $checkout = $checkoutService->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    expect(fn () => $checkoutService->placeOrder($checkout, [
        'payment_method' => PaymentMethod::CreditCard->value,
        'card_number' => '4000000000000002',
    ]))->toThrow(PaymentFailedException::class);

    $inventory = $variant->inventoryItem()->firstOrFail()->fresh();
    $checkout->refresh();
    $cart->refresh();

    expect(Order::query()->count())->toBe(0)
        ->and($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($cart->status)->toBe(CartStatus::Active)
        ->and($inventory->quantity_on_hand)->toBe(10)
        ->and($inventory->quantity_reserved)->toBe(0);
});

it('places bank-transfer orders in pending financial state', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        variantAttributes: ['requires_shipping' => false],
        inventoryAttributes: ['quantity_on_hand' => 10],
    );

    $cartService = app(CartService::class);
    $checkoutService = app(CheckoutService::class);

    $cart = $cartService->create($store);
    $cart = $cartService->addLine($cart, $variant->id, 2);

    $checkout = $checkoutService->createFromCart($cart, 'buyer@example.test');
    $checkout = $checkoutService->setAddress($checkout, null);
    $checkout = $checkoutService->setShippingMethod($checkout, null);
    $checkout = $checkoutService->selectPaymentMethod($checkout, PaymentMethod::BankTransfer);

    $order = $checkoutService->placeOrder($checkout, [
        'payment_method' => PaymentMethod::BankTransfer->value,
    ]);

    $payment = $order->payments()->first();
    $inventory = $variant->inventoryItem()->firstOrFail()->fresh();

    $checkout->refresh();
    $cart->refresh();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payment_method)->toBe(PaymentMethod::BankTransfer)
        ->and($payment?->status)->toBe(PaymentStatus::Pending)
        ->and($checkout->status)->toBe(CheckoutStatus::Completed)
        ->and($cart->status)->toBe(CartStatus::Converted)
        ->and($inventory->quantity_on_hand)->toBe(10)
        ->and($inventory->quantity_reserved)->toBe(2);
});
