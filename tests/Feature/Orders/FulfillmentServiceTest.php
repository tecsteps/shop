<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Services\FulfillmentService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeFulfillmentFixture(int $qty = 3): array
{
    $store = Store::factory()->create();
    $order = Order::factory()->paid()->create(['store_id' => $store->getKey()]);
    $line = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'quantity' => $qty,
    ]);

    return [$order, $line];
}

it('creates a fulfillment and transitions order to fulfilled when all lines covered', function () {
    [$order, $line] = makeFulfillmentFixture(2);

    $fulfillment = app(FulfillmentService::class)->create($order, [
        ['order_line_id' => (int) $line->getKey(), 'quantity' => 2],
    ]);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->refresh()->status)->toBe(OrderStatus::Fulfilled);
});

it('transitions fulfillment through partial when only some quantity is fulfilled', function () {
    [$order, $line] = makeFulfillmentFixture(5);

    app(FulfillmentService::class)->create($order, [
        ['order_line_id' => (int) $line->getKey(), 'quantity' => 2],
    ]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    app(FulfillmentService::class)->create($order, [
        ['order_line_id' => (int) $line->getKey(), 'quantity' => 3],
    ]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('rejects fulfilling more than unfulfilled quantity', function () {
    [$order, $line] = makeFulfillmentFixture(2);

    app(FulfillmentService::class)->create($order, [
        ['order_line_id' => (int) $line->getKey(), 'quantity' => 5],
    ]);
})->throws(RuntimeException::class);

it('blocks fulfillment when financial status is pending', function () {
    $store = Store::factory()->create();
    $order = Order::factory()->create([
        'store_id' => $store->getKey(),
        'financial_status' => FinancialStatus::Pending->value,
    ]);
    $line = OrderLine::factory()->create(['order_id' => $order->getKey(), 'quantity' => 1]);

    app(FulfillmentService::class)->create($order, [
        ['order_line_id' => (int) $line->getKey(), 'quantity' => 1],
    ]);
})->throws(FulfillmentGuardException::class);

it('transitions fulfillment from pending to shipped to delivered', function () {
    [$order, $line] = makeFulfillmentFixture(1);
    $fulfillment = app(FulfillmentService::class)->create($order, [
        ['order_line_id' => (int) $line->getKey(), 'quantity' => 1],
    ], ['tracking_company' => 'USPS', 'tracking_number' => 'Z123']);

    $fulfillment = app(FulfillmentService::class)->markAsShipped($fulfillment);
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull();

    $fulfillment = app(FulfillmentService::class)->markAsDelivered($fulfillment);
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('rejects marking delivered before shipped', function () {
    [$order, $line] = makeFulfillmentFixture(1);
    $fulfillment = app(FulfillmentService::class)->create($order, [
        ['order_line_id' => (int) $line->getKey(), 'quantity' => 1],
    ]);

    app(FulfillmentService::class)->markAsDelivered($fulfillment);
})->throws(RuntimeException::class);
