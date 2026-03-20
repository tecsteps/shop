<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Services\FulfillmentService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->fulfillmentService = app(FulfillmentService::class);
});

function createPaidOrderWithLines(Store $store, int $lineCount = 1, int $quantity = 2): Order
{
    $order = Order::factory()->paid()->create([
        'store_id' => $store->id,
    ]);

    for ($i = 0; $i < $lineCount; $i++) {
        OrderLine::factory()->create([
            'order_id' => $order->id,
            'quantity' => $quantity,
            'unit_price_amount' => 2500,
            'total_amount' => 2500 * $quantity,
        ]);
    }

    return $order;
}

it('creates a fulfillment for a paid order', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store);
    $line = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $line->id => $line->quantity,
    ], [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
    ]);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('123456')
        ->and($fulfillment->lines)->toHaveCount(1);
});

it('blocks fulfillment for pending payment', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Pending,
    ]);

    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    expect(fn () => $this->fulfillmentService->create($order, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

it('blocks fulfillment for voided payment', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Voided,
    ]);

    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    expect(fn () => $this->fulfillmentService->create($order, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

it('allows fulfillment for partially refunded order', function () {
    Event::fake();

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::PartiallyRefunded,
        'status' => OrderStatus::Paid,
    ]);

    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => 1]);

    expect($fulfillment)->not->toBeNull();
});

it('sets order to fulfilled when all lines are fulfilled', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store, 1, 2);
    $line = $order->lines->first();

    $this->fulfillmentService->create($order, [
        $line->id => 2,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class);
});

it('sets order to partial when some lines are fulfilled', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store, 1, 4);
    $line = $order->lines->first();

    $this->fulfillmentService->create($order, [
        $line->id => 2,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    Event::assertNotDispatched(OrderFulfilled::class);
});

it('prevents over-fulfillment', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store, 1, 2);
    $line = $order->lines->first();

    expect(fn () => $this->fulfillmentService->create($order, [
        $line->id => 5,
    ]))->toThrow(RuntimeException::class);
});

it('marks fulfillment as shipped', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store);
    $line = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $line->id => $line->quantity,
    ]);

    $this->fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'UPS',
        'tracking_number' => 'TRACK123',
        'tracking_url' => 'https://ups.com/track/TRACK123',
    ]);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->tracking_company)->toBe('UPS')
        ->and($fulfillment->shipped_at)->not->toBeNull();
});

it('marks fulfillment as delivered', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store);
    $line = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $line->id => $line->quantity,
    ]);

    $this->fulfillmentService->markAsShipped($fulfillment);

    $this->fulfillmentService->markAsDelivered($fulfillment);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('rejects shipping a non-pending fulfillment', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store);
    $line = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => $line->quantity]);
    $this->fulfillmentService->markAsShipped($fulfillment);

    expect(fn () => $this->fulfillmentService->markAsShipped($fulfillment->fresh()))
        ->toThrow(RuntimeException::class);
});

it('rejects delivering a non-shipped fulfillment', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store);
    $line = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => $line->quantity]);

    expect(fn () => $this->fulfillmentService->markAsDelivered($fulfillment))
        ->toThrow(RuntimeException::class);
});

it('fulfills multiple lines across multiple fulfillments', function () {
    Event::fake();

    $order = createPaidOrderWithLines($this->store, 2, 3);
    $lines = $order->lines;

    // First fulfillment: partial fulfillment of both lines
    $this->fulfillmentService->create($order, [
        $lines[0]->id => 2,
        $lines[1]->id => 1,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    // Second fulfillment: complete remaining
    $this->fulfillmentService->create($order->fresh(), [
        $lines[0]->id => 1,
        $lines[1]->id => 2,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);
});
