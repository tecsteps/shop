<?php

use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Services\FulfillmentService;

it('creates a fulfillment for specific order lines', function () {
    $order = makeCompletedOrder();
    $line = $order->lines()->first();

    $fulfillment = app(FulfillmentService::class)->create($order, [['order_line_id' => $line->id, 'quantity' => $line->quantity]]);

    expect($fulfillment->lines()->count())->toBe(1);
    expect($order->fresh()->fulfillment_status)->toBe('fulfilled');
});

it('adds tracking information', function () {
    $order = makeCompletedOrder();
    $line = $order->lines()->first();

    $fulfillment = app(FulfillmentService::class)->create($order, [['order_line_id' => $line->id, 'quantity' => 1]], ['tracking_company' => 'DHL', 'tracking_number' => '123456']);

    app(FulfillmentService::class)->markAsShipped($fulfillment);

    expect($fulfillment->fresh()->status)->toBe('shipped');
    expect($fulfillment->fresh()->tracking_number)->toBe('123456');
    expect($fulfillment->fresh()->shipped_at)->not->toBeNull();
});

it('transitions fulfillment from shipped to delivered', function () {
    $order = makeCompletedOrder();
    $line = $order->lines()->first();
    $fulfillment = app(FulfillmentService::class)->create($order, [['order_line_id' => $line->id, 'quantity' => 1]]);
    app(FulfillmentService::class)->markAsShipped($fulfillment);

    app(FulfillmentService::class)->markAsDelivered($fulfillment);

    expect($fulfillment->fresh()->status)->toBe('delivered');
});

it('prevents fulfilling more than ordered quantity', function () {
    $order = makeCompletedOrder();
    $line = $order->lines()->first();

    expect(fn () => app(FulfillmentService::class)->create($order, [['order_line_id' => $line->id, 'quantity' => $line->quantity + 1]]))
        ->toThrow(InvalidArgumentException::class);
});

it('fulfillment guard blocks fulfillment when financial_status is pending', function () {
    $ctx = createStoreContext();
    $order = Order::factory()->create(['store_id' => $ctx['store']->id, 'financial_status' => 'pending', 'status' => 'pending', 'payment_method' => 'bank_transfer']);
    $line = \App\Models\OrderLine::factory()->create(['order_id' => $order->id]);

    expect(fn () => app(FulfillmentService::class)->create($order, [['order_line_id' => $line->id, 'quantity' => 1]]))
        ->toThrow(FulfillmentGuardException::class);
});
