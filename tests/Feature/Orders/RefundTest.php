<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->refundService = app(RefundService::class);
});

it('creates a full refund', function () {
    Event::fake();

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    $refund = $this->refundService->create($order, $payment, 5000, 'Customer request');

    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->amount)->toBe(5000)
        ->and($refund->reason)->toBe('Customer request')
        ->and($refund->provider_refund_id)->toStartWith('mock_refund_');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded);

    Event::assertDispatched(OrderRefunded::class);
});

it('creates a partial refund', function () {
    Event::fake();

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    $refund = $this->refundService->create($order, $payment, 2000);

    expect($refund->amount)->toBe(2000);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('rejects refund exceeding payment amount', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    expect(fn () => $this->refundService->create($order, $payment, 6000))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects refund exceeding remaining refundable amount', function () {
    Event::fake();

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    // First refund
    $this->refundService->create($order, $payment, 3000);

    // Second refund exceeding remaining
    expect(fn () => $this->refundService->create($order, $payment, 3000))
        ->toThrow(InvalidArgumentException::class);
});

it('restocks inventory when restock flag is true', function () {
    Event::fake();

    $variant = ProductVariant::factory()->create();
    $inventoryItem = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 8,
        'quantity_reserved' => 0,
    ]);

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    $this->refundService->create($order, $payment, 5000, 'Restock needed', true);

    $inventoryItem->refresh();
    expect($inventoryItem->quantity_on_hand)->toBe(10);
});

it('does not restock when restock flag is false', function () {
    Event::fake();

    $variant = ProductVariant::factory()->create();
    $inventoryItem = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 8,
    ]);

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    $this->refundService->create($order, $payment, 5000, null, false);

    $inventoryItem->refresh();
    expect($inventoryItem->quantity_on_hand)->toBe(8);
});

it('transitions to fully refunded after multiple partial refunds', function () {
    Event::fake();

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 5000]);

    $this->refundService->create($order, $payment, 2000);
    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $this->refundService->create($order, $payment, 3000);
    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded);
});
