<?php

use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Services\FulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);

    $this->fulfillmentService = new FulfillmentService;

    $this->order = Order::factory()->for($this->store)->paid()->create([
        'subtotal_amount' => 6000,
        'total_amount' => 6000,
    ]);

    $this->line1 = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'title_snapshot' => 'Line A',
        'quantity' => 2,
        'unit_price_amount' => 2000,
        'total_amount' => 4000,
    ]);

    $this->line2 = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'title_snapshot' => 'Line B',
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'total_amount' => 2000,
    ]);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a fulfillment for a paid order', function (): void {
    $fulfillment = $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 2, $this->line2->id => 1],
        ['company' => 'DHL', 'number' => 'TRACK123', 'url' => 'https://example.com/track/TRACK123'],
    );

    expect($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('TRACK123')
        ->and($fulfillment->lines)->toHaveCount(2)
        ->and($this->order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Fulfilled);
});

it('marks partial fulfillment when only some lines are shipped', function (): void {
    $this->fulfillmentService->create($this->order, [$this->line1->id => 1]);

    expect($this->order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('marks a fulfillment as shipped with tracking', function (): void {
    $fulfillment = $this->fulfillmentService->create($this->order, [$this->line1->id => 2]);

    $this->fulfillmentService->markAsShipped($fulfillment, [
        'company' => 'UPS',
        'number' => 'UPS9999',
        'url' => 'https://ups.com/track',
    ]);

    expect($fulfillment->fresh()->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->fresh()->shipped_at)->not->toBeNull()
        ->and($fulfillment->fresh()->tracking_company)->toBe('UPS');
});

it('marks a fulfillment as delivered and dispatches event', function (): void {
    \Illuminate\Support\Facades\Event::fake(\App\Events\FulfillmentDelivered::class);

    $fulfillment = $this->fulfillmentService->create($this->order, [$this->line1->id => 2]);
    $this->fulfillmentService->markAsShipped($fulfillment, ['company' => 'DHL', 'number' => 'X']);
    $this->fulfillmentService->markAsDelivered($fulfillment->fresh());

    expect($fulfillment->fresh()->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->fresh()->delivered_at)->not->toBeNull();

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\FulfillmentDelivered::class);
});

it('guard rejects fulfillment for an unpaid order', function (): void {
    $unpaidOrder = Order::factory()->for($this->store)->create([
        'subtotal_amount' => 1000,
        'total_amount' => 1000,
    ]);
    $line = OrderLine::factory()->create([
        'order_id' => $unpaidOrder->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
    ]);

    expect(fn () => $this->fulfillmentService->create($unpaidOrder, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

it('updates order to fulfilled after two partial fulfillments complete it', function (): void {
    $this->fulfillmentService->create($this->order, [$this->line1->id => 2]);
    expect($this->order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    $this->fulfillmentService->create($this->order->fresh(), [$this->line2->id => 1]);
    expect($this->order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Fulfilled);
});
