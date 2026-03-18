<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPaid;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

function createBankTransferContext(bool $digital = false): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $variant = ProductVariant::create([
        'product_id' => \App\Models\Product::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'title' => 'BT Product',
            'handle' => 'bt-product-'.rand(1000, 9999),
            'status' => 'active',
            'published_at' => now(),
        ])->id,
        'sku' => 'BT-001',
        'price_amount' => 5000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
        'requires_shipping' => ! $digital,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 2,
        'policy' => 'deny',
    ]);

    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#1001',
        'payment_method' => PaymentMethod::BankTransfer,
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'currency' => 'EUR',
        'total_amount' => 5000,
        'placed_at' => now()->subDays(2),
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'BT Product',
        'price_amount' => 5000,
        'quantity' => 2,
        'total_amount' => 10000,
        'requires_shipping' => ! $digital,
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'mock',
        'method' => PaymentMethod::BankTransfer,
        'provider_payment_id' => 'mock_bt_123',
        'status' => PaymentStatus::Pending,
        'amount' => 5000,
        'currency' => 'EUR',
        'created_at' => now(),
    ]);

    return array_merge($ctx, compact('order', 'payment', 'variant', 'inventory'));
}

it('confirms bank transfer payment', function () {
    $ctx = createBankTransferContext();
    Event::fake([OrderPaid::class]);
    $orderService = app(OrderService::class);

    $orderService->confirmBankTransferPayment($ctx['order']);

    $order = $ctx['order']->fresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid);

    $payment = $ctx['payment']->fresh();
    expect($payment->status)->toBe(PaymentStatus::Captured);

    $item = $ctx['inventory']->fresh();
    expect($item->quantity_on_hand)->toBe(8)
        ->and($item->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderPaid::class);
});

it('rejects confirmation for non-bank-transfer order', function () {
    $ctx = createBankTransferContext();
    $ctx['order']->update(['payment_method' => PaymentMethod::CreditCard]);
    $orderService = app(OrderService::class);

    expect(fn () => $orderService->confirmBankTransferPayment($ctx['order']->fresh()))
        ->toThrow(RuntimeException::class);
});

it('rejects confirmation when not pending', function () {
    $ctx = createBankTransferContext();
    $ctx['order']->update(['financial_status' => FinancialStatus::Paid]);
    $orderService = app(OrderService::class);

    expect(fn () => $orderService->confirmBankTransferPayment($ctx['order']->fresh()))
        ->toThrow(RuntimeException::class);
});

it('auto-fulfills digital order on payment confirmation', function () {
    $ctx = createBankTransferContext(digital: true);
    $orderService = app(OrderService::class);

    $orderService->confirmBankTransferPayment($ctx['order']);

    $order = $ctx['order']->fresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    $fulfillment = $order->fulfillments()->first();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('cancels unpaid bank transfer orders after timeout', function () {
    $ctx = createBankTransferContext();
    Event::fake([OrderCancelled::class]);

    // Set placed_at to 8 days ago (past the 7-day default)
    $ctx['order']->update(['placed_at' => now()->subDays(8)]);

    $job = new CancelUnpaidBankTransferOrders;
    $job->handle(app(OrderService::class));

    $order = $ctx['order']->fresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->financial_status)->toBe(FinancialStatus::Voided);

    $item = $ctx['inventory']->fresh();
    expect($item->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderCancelled::class);
});

it('does not cancel recent bank transfer orders', function () {
    $ctx = createBankTransferContext();

    // placed_at is only 2 days ago - should not be cancelled
    $job = new CancelUnpaidBankTransferOrders;
    $job->handle(app(OrderService::class));

    $order = $ctx['order']->fresh();
    expect($order->status)->toBe(OrderStatus::Pending);
});
