<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RefundService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->refundService = app(RefundService::class);
});

function createPaidOrderWithPayment($store, int $totalAmount = 5000): array
{
    $order = Order::factory()->create([
        'store_id' => $store->id,
        'total_amount' => $totalAmount,
        'financial_status' => FinancialStatus::Paid,
        'status' => OrderStatus::Paid,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => $totalAmount,
        'status' => PaymentStatus::Captured,
    ]);

    return [$order, $payment];
}

it('creates a partial refund and updates financial status to partially refunded', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $refund = $this->refundService->create($order, $payment, 2000, 'Partial refund');

    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->amount)->toBe(2000)
        ->and($refund->reason)->toBe('Partial refund');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('creates a full refund and updates order to refunded status', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $refund = $this->refundService->create($order, $payment, 5000, 'Full refund');

    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->amount)->toBe(5000);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded);

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Refunded);
});

it('throws when refund amount exceeds refundable amount', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $this->refundService->create($order, $payment, 6000);
})->throws(\RuntimeException::class, 'exceeds refundable amount');

it('throws when refund amount exceeds payment amount', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 10000,
        'financial_status' => FinancialStatus::Paid,
        'status' => OrderStatus::Paid,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'status' => PaymentStatus::Captured,
    ]);

    $this->refundService->create($order, $payment, 6000);
})->throws(\RuntimeException::class, 'exceeds payment amount');

it('tracks cumulative refunds correctly', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $this->refundService->create($order, $payment, 2000, 'First refund');
    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $this->refundService->create($order, $payment, 3000, 'Second refund');
    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded)
        ->and($order->refunds)->toHaveCount(2);
});

it('prevents refund exceeding remaining refundable amount after partial refund', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $this->refundService->create($order, $payment, 3000);
    $order->refresh();

    $this->refundService->create($order, $payment, 3000);
})->throws(\RuntimeException::class, 'exceeds refundable amount');

it('restocks inventory when restock flag is true', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);
    $inventoryItem = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 8,
        'quantity_reserved' => 0,
    ]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'financial_status' => FinancialStatus::Paid,
        'status' => OrderStatus::Paid,
    ]);
    OrderLine::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
        'tax_lines_json' => [],
        'discount_allocations_json' => [],
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'status' => PaymentStatus::Captured,
    ]);

    $this->refundService->create($order, $payment, 5000, 'Defective', restock: true);

    $inventoryItem->refresh();
    expect($inventoryItem->quantity_on_hand)->toBe(10);
});
