<?php

use App\Services\RefundService;

it('creates a full refund', function () {
    $order = makeCompletedOrder();

    $payment = $order->payments()->first();
    app(RefundService::class)->create($order, $payment, $order->total_amount, 'Customer requested', false);

    expect($order->fresh()->financial_status)->toBe('refunded');
    expect($order->fresh()->status)->toBe('refunded');
});

it('creates a partial refund', function () {
    $order = makeCompletedOrder();

    app(RefundService::class)->create($order, $order->payments()->first(), 2000, null, false);

    expect($order->fresh()->financial_status)->toBe('partially_refunded');
});

it('rejects refund exceeding payment amount', function () {
    $order = makeCompletedOrder();

    expect(fn () => app(RefundService::class)->create($order, $order->payments()->first(), $order->total_amount + 100, null, false))
        ->toThrow(InvalidArgumentException::class);
});

it('restocks inventory when restock flag is true', function () {
    $order = makeCompletedOrder();
    $line = $order->lines()->first();
    $variant = $line->variant;

    $before = $variant->inventoryItem->fresh()->quantity_on_hand;

    app(RefundService::class)->create($order, $order->payments()->first(), $order->total_amount, null, true);

    expect($variant->inventoryItem->fresh()->quantity_on_hand)->toBe($before + $line->quantity);
});

it('does not restock when restock flag is false', function () {
    $order = makeCompletedOrder();
    $variant = $order->lines()->first()->variant;
    $before = $variant->inventoryItem->fresh()->quantity_on_hand;

    app(RefundService::class)->create($order, $order->payments()->first(), $order->total_amount, null, false);

    expect($variant->inventoryItem->fresh()->quantity_on_hand)->toBe($before);
});

it('records refund reason', function () {
    $order = makeCompletedOrder();

    $refund = app(RefundService::class)->create($order, $order->payments()->first(), $order->total_amount, 'Customer requested', false);

    expect($refund->reason)->toBe('Customer requested');
});
