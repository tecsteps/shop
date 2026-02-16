<?php

use App\Enums\FinancialStatus;
use App\Enums\RefundStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\RefundService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->refundService = app(RefundService::class);
});

it('creates a full refund', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create(['total' => 5000]);
    $payment = Payment::factory()->for($order)->create(['amount' => 5000]);

    $refund = $this->refundService->create($order, $payment, 5000, 'Requested', false);

    expect($refund->amount)->toBe(5000)
        ->and($refund->status)->toBe(RefundStatus::Processed)
        ->and($order->fresh()->financial_status)->toBe(FinancialStatus::Refunded);
});

it('creates a partial refund', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create(['total' => 5000]);
    $payment = Payment::factory()->for($order)->create(['amount' => 5000]);

    $refund = $this->refundService->create($order, $payment, 2000, 'Partial', false);

    expect($refund->amount)->toBe(2000)
        ->and($order->fresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('restocks inventory when restock flag is true', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create(['total' => 5000]);
    $payment = Payment::factory()->for($order)->create(['amount' => 5000]);

    $variant = ProductVariant::factory()->create();
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
    ]);
    OrderLine::factory()->for($order)->create(['variant_id' => $variant->id, 'quantity' => 2, 'product_id' => $variant->product_id]);

    $this->refundService->create($order, $payment, 5000, 'Restock', true);

    expect($item->fresh()->quantity_on_hand)->toBe(7);
});

it('does not restock when restock flag is false', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create(['total' => 5000]);
    $payment = Payment::factory()->for($order)->create(['amount' => 5000]);

    $variant = ProductVariant::factory()->create();
    $item = InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
    ]);
    OrderLine::factory()->for($order)->create(['variant_id' => $variant->id, 'quantity' => 2, 'product_id' => $variant->product_id]);

    $this->refundService->create($order, $payment, 5000, 'No restock', false);

    expect($item->fresh()->quantity_on_hand)->toBe(5);
});

it('records refund reason', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create(['total' => 5000]);
    $payment = Payment::factory()->for($order)->create(['amount' => 5000]);

    $refund = $this->refundService->create($order, $payment, 5000, 'Customer requested', false);

    expect($refund->reason)->toBe('Customer requested');
});
