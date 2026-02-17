<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPaid;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->orderService = app(OrderService::class);
});

it('confirms bank transfer payment and updates statuses', function () {
    Event::fake();

    $order = Order::factory()->bankTransfer()->create(['store_id' => $this->store->id]);
    Payment::factory()->pending()->create(['order_id' => $order->id, 'amount' => $order->total_amount]);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->captured_at)->not->toBeNull();

    Event::assertDispatched(OrderPaid::class);
});

it('rejects confirmation for non-bank-transfer orders', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
    ]);

    expect(fn () => $this->orderService->confirmBankTransferPayment($order))
        ->toThrow(RuntimeException::class);
});

it('rejects confirmation for already paid orders', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::BankTransfer,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
    ]);

    expect(fn () => $this->orderService->confirmBankTransferPayment($order))
        ->toThrow(RuntimeException::class);
});

it('auto-fulfills digital products on bank transfer confirmation', function () {
    Event::fake();

    $order = Order::factory()->bankTransfer()->create(['store_id' => $this->store->id]);
    Payment::factory()->pending()->create(['order_id' => $order->id, 'amount' => $order->total_amount]);
    OrderLine::factory()->digital()->create(['order_id' => $order->id]);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled)
        ->and($order->fulfillments)->toHaveCount(1);
});

it('auto-cancels unpaid bank transfer orders after 7 days', function () {
    Event::fake();

    $order = Order::factory()->bankTransfer()->create([
        'store_id' => $this->store->id,
        'placed_at' => now()->subDays(8),
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id]);

    (new CancelUnpaidBankTransferOrders)->handle(app(\App\Services\InventoryService::class));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->financial_status)->toBe(FinancialStatus::Voided);

    Event::assertDispatched(OrderCancelled::class);
});

it('does not cancel bank transfer orders within 7 days', function () {
    Event::fake();

    $order = Order::factory()->bankTransfer()->create([
        'store_id' => $this->store->id,
        'placed_at' => now()->subDays(5),
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id]);

    (new CancelUnpaidBankTransferOrders)->handle(app(\App\Services\InventoryService::class));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending);

    Event::assertNotDispatched(OrderCancelled::class);
});
