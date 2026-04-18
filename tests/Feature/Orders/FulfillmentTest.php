<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\FulfillmentService;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
});

it('creates a pending fulfillment when order is paid', function (): void {
    Event::fake([OrderFulfilled::class]);

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    $fulfillment = app(FulfillmentService::class)->create($order->fresh(), [
        ['order_line_id' => $line->id, 'quantity' => 2],
    ], ['tracking_company' => 'UPS', 'tracking_number' => '1Z']);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);
    expect($fulfillment->tracking_company)->toBe('UPS');
    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->fresh()->status)->toBe(OrderStatus::Fulfilled);
    Event::assertDispatched(OrderFulfilled::class);
});

it('blocks fulfillment when financial_status is pending', function (): void {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Pending,
    ]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    expect(fn () => app(FulfillmentService::class)->create($order, [
        ['order_line_id' => $line->id, 'quantity' => 1],
    ]))->toThrow(FulfillmentGuardException::class);
});

it('allows fulfillment when partially refunded', function (): void {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::PartiallyRefunded,
    ]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    $fulfillment = app(FulfillmentService::class)->create($order, [
        ['order_line_id' => $line->id, 'quantity' => 1],
    ]);

    expect($fulfillment->id)->not->toBeNull();
});

it('marks partial fulfillment correctly', function (): void {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 3]);

    app(FulfillmentService::class)->create($order, [
        ['order_line_id' => $line->id, 'quantity' => 1],
    ]);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('rejects quantity exceeding unfulfilled amount', function (): void {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    app(FulfillmentService::class)->create($order, [
        ['order_line_id' => $line->id, 'quantity' => 2],
    ]);

    expect(fn () => app(FulfillmentService::class)->create($order->fresh(), [
        ['order_line_id' => $line->id, 'quantity' => 1],
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('marks a fulfillment as shipped and then delivered', function (): void {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    $fulfillment = app(FulfillmentService::class)->create($order, [
        ['order_line_id' => $line->id, 'quantity' => 1],
    ]);

    $shipped = app(FulfillmentService::class)->markAsShipped($fulfillment, ['tracking_number' => 'TRK1']);
    expect($shipped->status)->toBe(FulfillmentShipmentStatus::Shipped);
    expect($shipped->shipped_at)->not->toBeNull();

    $delivered = app(FulfillmentService::class)->markAsDelivered($shipped);
    expect($delivered->status)->toBe(FulfillmentShipmentStatus::Delivered);
    expect($delivered->delivered_at)->not->toBeNull();
});
