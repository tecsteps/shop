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
    $this->fulfillmentService = app(FulfillmentService::class);
});

it('creates a fulfillment for a paid order', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 3]);

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => 3]);

    expect($fulfillment)->not->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($fulfillment->lines)->toHaveCount(1)
        ->and($fulfillment->lines->first()->quantity)->toBe(3);
});

it('blocks fulfillment for pending financial status', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Pending,
    ]);
    $line = OrderLine::factory()->create(['order_id' => $order->id]);

    expect(fn () => $this->fulfillmentService->create($order, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

it('blocks fulfillment for voided financial status', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Voided,
    ]);
    $line = OrderLine::factory()->create(['order_id' => $order->id]);

    expect(fn () => $this->fulfillmentService->create($order, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

it('allows fulfillment for partially refunded order', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::PartiallyRefunded,
    ]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => 2]);

    expect($fulfillment)->not->toBeNull();
});

it('updates order to fulfilled when all lines are fulfilled', function () {
    Event::fake();

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    $this->fulfillmentService->create($order, [$line->id => 2]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class);
});

it('updates order to partial when some lines are fulfilled', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 5]);

    $this->fulfillmentService->create($order, [$line->id => 3]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('rejects fulfillment exceeding unfulfilled quantity', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    expect(fn () => $this->fulfillmentService->create($order, [$line->id => 5]))
        ->toThrow(InvalidArgumentException::class);
});

it('creates fulfillment with tracking data', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => 1], [
        'tracking_company' => 'DHL',
        'tracking_number' => 'DE123456789',
        'tracking_url' => 'https://tracking.dhl.com/DE123456789',
    ]);

    expect($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('DE123456789')
        ->and($fulfillment->tracking_url)->toBe('https://tracking.dhl.com/DE123456789');
});

it('marks fulfillment as shipped', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => 1]);

    $this->fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => 'ABC123',
    ]);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('ABC123');
});

it('marks fulfillment as delivered', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    $fulfillment = $this->fulfillmentService->create($order, [$line->id => 1]);
    $this->fulfillmentService->markAsShipped($fulfillment);
    $this->fulfillmentService->markAsDelivered($fulfillment);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->delivered_at)->not->toBeNull();
});

it('handles multiple partial fulfillments', function () {
    Event::fake();

    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $line1 = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 3]);
    $line2 = OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    // Fulfill line1 partially
    $this->fulfillmentService->create($order, [$line1->id => 2]);
    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    // Fulfill remaining line1 and all of line2
    $this->fulfillmentService->create($order, [$line1->id => 1, $line2->id => 2]);
    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class);
});
