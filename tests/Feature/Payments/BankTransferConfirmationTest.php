<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->orderService = app(OrderService::class);
});

/**
 * A real bank transfer order created through the checkout flow, leaving the
 * inventory reserved (not committed).
 *
 * @param  array<string, mixed>  $variantAttributes
 */
function bankTransferOrder($test, int $quantity = 2, array $variantAttributes = []): Order
{
    $checkout = createPaymentSelectedCheckout(
        $test->store,
        'bank_transfer',
        quantity: $quantity,
        quantityOnHand: 10,
        variantAttributes: $variantAttributes,
    );

    return app(CheckoutService::class)->completeCheckout($checkout);
}

it('admin can confirm bank transfer payment', function () {
    $order = bankTransferOrder($this);

    $item = $order->lines->first()->variant->inventoryItem;
    expect($item->refresh()->quantity_reserved)->toBe(2);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->payments->first()->status)->toBe(PaymentStatus::Captured);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(8);
    expect($item->quantity_reserved)->toBe(0);
});

it('cannot confirm payment for non-bank-transfer orders', function () {
    $order = Order::factory()->for($this->store)->create();

    $this->orderService->confirmBankTransferPayment($order);
})->throws(ValidationException::class);

it('cannot confirm already confirmed payment', function () {
    $order = Order::factory()->paid()->for($this->store)->create([
        'payment_method' => 'bank_transfer',
    ]);

    $this->orderService->confirmBankTransferPayment($order);
})->throws(ValidationException::class);

it('auto-cancel job cancels unpaid bank transfer orders after config days', function () {
    $order = bankTransferOrder($this);
    $order->forceFill(['placed_at' => now()->subDays(8)])->save();

    $item = $order->lines->first()->variant->inventoryItem;
    expect($item->refresh()->quantity_reserved)->toBe(2);

    (new CancelUnpaidBankTransferOrders)->handle($this->orderService);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
    expect($order->financial_status)->toBe(FinancialStatus::Voided);
    expect($item->refresh()->quantity_reserved)->toBe(0);
});

it('auto-cancel job does not cancel orders within config days', function () {
    $order = bankTransferOrder($this);
    $order->forceFill(['placed_at' => now()->subDays(2)])->save();

    (new CancelUnpaidBankTransferOrders)->handle($this->orderService);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->financial_status)->toBe(FinancialStatus::Pending);
});

it('auto-fulfills digital products on payment confirmation', function () {
    $order = bankTransferOrder($this, variantAttributes: ['requires_shipping' => false]);
    expect($order->fulfillments)->toHaveCount(0);

    $this->orderService->confirmBankTransferPayment($order);

    $fulfillment = $order->refresh()->fulfillments->first();
    expect($fulfillment)->not->toBeNull();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});
