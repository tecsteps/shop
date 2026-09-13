<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function bankTransferOrder(array $lines = [['price' => 2500, 'qty' => 2]]): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, $lines);
    $rate = CommerceTestHelper::flatShippingRate($store);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::BankTransfer);
    $order = $checkouts->pay($checkout, PaymentMethod::BankTransfer, []);

    return ['store' => $store, 'cart' => $cart, 'checkout' => $checkout, 'order' => $order];
}

it('admin can confirm bank transfer payment', function () {
    $ctx = bankTransferOrder();

    $order = app(OrderService::class)->confirmBankTransferPayment($ctx['order']);

    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->payments()->first()->status)->toBe(PaymentStatus::Captured);

    $item = App\Models\InventoryItem::query()
        ->where('variant_id', $ctx['cart']->cartLines()->first()->variant_id)
        ->first();
    expect($item->quantity_on_hand)->toBe(48);
    expect($item->quantity_reserved)->toBe(0);
});

it('cannot confirm payment for non-bank-transfer orders', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    app(OrderService::class)->confirmBankTransferPayment($order);
})->throws(InvalidOrderTransitionException::class);

it('cannot confirm already confirmed payment', function () {
    $ctx = bankTransferOrder();

    app(OrderService::class)->confirmBankTransferPayment($ctx['order']);
    app(OrderService::class)->confirmBankTransferPayment($ctx['order']->refresh());
})->throws(InvalidOrderTransitionException::class);

it('auto-cancel job cancels unpaid bank transfer orders after config days', function () {
    $ctx = bankTransferOrder();
    $ctx['order']->placed_at = now()->subDays(8);
    $ctx['order']->save();

    app(App\Jobs\CancelUnpaidBankTransferOrders::class)->handle();

    $order = $ctx['order']->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
    expect($order->financial_status)->toBe(FinancialStatus::Voided);

    $item = App\Models\InventoryItem::query()
        ->where('variant_id', $ctx['cart']->cartLines()->first()->variant_id)
        ->first();
    expect($item->quantity_reserved)->toBe(0);
});

it('auto-cancel job does not cancel orders within config days', function () {
    $ctx = bankTransferOrder();
    $ctx['order']->placed_at = now()->subDays(2);
    $ctx['order']->save();

    app(App\Jobs\CancelUnpaidBankTransferOrders::class)->handle();

    $order = $ctx['order']->refresh();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->financial_status)->toBe(FinancialStatus::Pending);
});

it('auto-fulfills digital products on payment confirmation', function () {
    $ctx = bankTransferOrder([['price' => 2500, 'qty' => 1, 'shipping' => false]]);

    $order = app(OrderService::class)->confirmBankTransferPayment($ctx['order']);

    $fulfillment = $order->fulfillments()->first();
    expect($fulfillment)->not->toBeNull();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});
