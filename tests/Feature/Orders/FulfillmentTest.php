<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Services\FulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->order = Order::factory()->for($this->store)->create(['financial_status' => FinancialStatus::Paid]);
    $this->firstLine = OrderLine::factory()->for($this->order)->create(['quantity' => 2]);
    $this->secondLine = OrderLine::factory()->for($this->order)->create(['quantity' => 1]);
    $this->service = app(FulfillmentService::class);
});

it('creates partial and complete fulfillments without over fulfilling', function () {
    $first = $this->service->create($this->order, [$this->firstLine->id => 2]);
    expect($first->lines)->toHaveCount(1)
        ->and($this->order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    $this->service->create($this->order->refresh(), [$this->secondLine->id => 1]);
    expect($this->order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('blocks fulfillment before payment', function () {
    $this->order->update(['financial_status' => FinancialStatus::Pending]);

    expect(fn () => $this->service->create($this->order->refresh(), [$this->firstLine->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

it('marks fulfillments shipped and delivered', function () {
    $fulfillment = $this->service->create($this->order, [$this->firstLine->id => 2]);
    $this->service->markAsShipped($fulfillment, ['tracking_company' => 'DHL', 'tracking_number' => '123456']);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull();

    $this->service->markAsDelivered($fulfillment);
    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->delivered_at)->not->toBeNull();
});
