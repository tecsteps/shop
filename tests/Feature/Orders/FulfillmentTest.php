<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\FulfillmentService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(FulfillmentService::class);
});

it('creates a fulfillment for specific order lines', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 3]);

    $fulfillment = $this->service->create($order, [$line->id => 3]);

    expect($fulfillment->lines)->toHaveCount(1)
        ->and($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);
});

it('updates order fulfillment status to partial', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create();
    $line1 = OrderLine::factory()->for($order)->create(['quantity' => 3]);
    $line2 = OrderLine::factory()->for($order)->create(['quantity' => 2]);

    $this->service->create($order, [$line1->id => 3]);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('updates order fulfillment status to fulfilled when all lines done', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 3]);

    $this->service->create($order, [$line->id => 3]);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('adds tracking information', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);

    $fulfillment = $this->service->create($order, [$line->id => 1], [
        'company' => 'DHL',
        'number' => '123456',
    ]);

    expect($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('123456');
});

it('transitions fulfillment from pending to shipped', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);
    $fulfillment = $this->service->create($order, [$line->id => 1]);

    $this->service->markAsShipped($fulfillment, ['company' => 'DHL', 'number' => '999']);

    expect($fulfillment->fresh()->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->fresh()->shipped_at)->not->toBeNull();
});

it('transitions fulfillment from shipped to delivered', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);
    $fulfillment = $this->service->create($order, [$line->id => 1]);
    $this->service->markAsShipped($fulfillment);

    $this->service->markAsDelivered($fulfillment);

    expect($fulfillment->fresh()->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('fulfillment guard blocks fulfillment when financial_status is pending', function () {
    $order = Order::factory()->for($this->ctx['store'])->create([
        'financial_status' => FinancialStatus::Pending,
    ]);
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);

    $this->service->create($order, [$line->id => 1]);
})->throws(FulfillmentGuardException::class);

it('fulfillment guard allows fulfillment when financial_status is paid', function () {
    $order = Order::factory()->paid()->for($this->ctx['store'])->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);

    $fulfillment = $this->service->create($order, [$line->id => 1]);
    expect($fulfillment)->not->toBeNull();
});

it('fulfillment guard allows fulfillment when financial_status is partially_refunded', function () {
    $order = Order::factory()->for($this->ctx['store'])->create([
        'financial_status' => FinancialStatus::PartiallyRefunded,
    ]);
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);

    $fulfillment = $this->service->create($order, [$line->id => 1]);
    expect($fulfillment)->not->toBeNull();
});
