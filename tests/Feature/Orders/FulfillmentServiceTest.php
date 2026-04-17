<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Store;
use App\Services\FulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(FulfillmentService::class);
});

function createPaidOrderWithLines(Store $store, array $lineQuantities = [2, 3]): array
{
    $order = Order::factory()->create([
        'store_id' => $store->id,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    $lines = [];
    foreach ($lineQuantities as $qty) {
        $lines[] = $order->lines()->create([
            'title_snapshot' => fake()->words(2, true),
            'quantity' => $qty,
            'unit_price_amount' => 1000,
            'total_amount' => $qty * 1000,
        ]);
    }

    return [$order, $lines];
}

test('create fulfillment for all lines marks order as fulfilled', function () {
    Event::fake();

    [$order, $lines] = createPaidOrderWithLines($this->store, [2, 3]);

    $fulfillment = $this->service->create($order, [
        $lines[0]->id => 2,
        $lines[1]->id => 3,
    ], [
        'tracking_company' => 'UPS',
        'tracking_number' => '1Z999AA10123456784',
    ]);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);
    expect($fulfillment->tracking_company)->toBe('UPS');
    expect($fulfillment->tracking_number)->toBe('1Z999AA10123456784');
    expect($fulfillment->lines)->toHaveCount(2);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class);
});

test('create partial fulfillment marks order as partial', function () {
    Event::fake();

    [$order, $lines] = createPaidOrderWithLines($this->store, [5]);

    $fulfillment = $this->service->create($order, [
        $lines[0]->id => 3,
    ]);

    expect($fulfillment->lines)->toHaveCount(1);
    expect($fulfillment->lines->first()->quantity)->toBe(3);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

test('multiple fulfillments complete the order', function () {
    Event::fake();

    [$order, $lines] = createPaidOrderWithLines($this->store, [5]);

    // First partial fulfillment
    $this->service->create($order, [$lines[0]->id => 3]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    // Second fulfillment completes it
    $this->service->create($order, [$lines[0]->id => 2]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class);
});

test('cannot fulfill more than ordered quantity', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, [2]);

    expect(fn () => $this->service->create($order, [$lines[0]->id => 5]))
        ->toThrow(InvalidArgumentException::class);
});

test('cannot fulfill already fulfilled line quantity', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, [3]);

    // Fulfill 2 out of 3
    $this->service->create($order, [$lines[0]->id => 2]);

    // Try to fulfill 2 more (only 1 remaining)
    expect(fn () => $this->service->create($order, [$lines[0]->id => 2]))
        ->toThrow(InvalidArgumentException::class);
});

test('guard blocks fulfillment for pending financial status', function () {
    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
    ]);

    $line = $order->lines()->create([
        'title_snapshot' => 'Test',
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
    ]);

    expect(fn () => $this->service->create($order, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

test('guard blocks fulfillment for voided financial status', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Voided,
    ]);

    $line = $order->lines()->create([
        'title_snapshot' => 'Test',
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
    ]);

    expect(fn () => $this->service->create($order, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

test('guard blocks fulfillment for refunded financial status', function () {
    $order = Order::factory()->refunded()->create([
        'store_id' => $this->store->id,
    ]);

    $line = $order->lines()->create([
        'title_snapshot' => 'Test',
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
    ]);

    expect(fn () => $this->service->create($order, [$line->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

test('guard allows fulfillment for partially_refunded financial status', function () {
    Event::fake();

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::PartiallyRefunded,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    $line = $order->lines()->create([
        'title_snapshot' => 'Test',
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
    ]);

    $fulfillment = $this->service->create($order, [$line->id => 1]);

    expect($fulfillment)->toBeInstanceOf(Fulfillment::class);
});

test('markAsShipped sets shipped status and timestamp', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, [2]);

    $fulfillment = $this->service->create($order, [$lines[0]->id => 2]);

    $result = $this->service->markAsShipped($fulfillment, [
        'tracking_company' => 'FedEx',
        'tracking_number' => 'FX123456',
        'tracking_url' => 'https://fedex.com/track/FX123456',
    ]);

    expect($result->status)->toBe(FulfillmentShipmentStatus::Shipped);
    expect($result->tracking_company)->toBe('FedEx');
    expect($result->tracking_number)->toBe('FX123456');
    expect($result->tracking_url)->toBe('https://fedex.com/track/FX123456');
    expect($result->shipped_at)->not->toBeNull();
});

test('markAsShipped fails if not in pending status', function () {
    $fulfillment = Fulfillment::factory()->shipped()->create([
        'order_id' => Order::factory()->create(['store_id' => $this->store->id])->id,
    ]);

    expect(fn () => $this->service->markAsShipped($fulfillment))
        ->toThrow(InvalidArgumentException::class);
});

test('markAsDelivered sets delivered status', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, [1]);

    $fulfillment = $this->service->create($order, [$lines[0]->id => 1]);
    $this->service->markAsShipped($fulfillment);

    $fulfillment->refresh();
    $result = $this->service->markAsDelivered($fulfillment);

    expect($result->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

test('markAsDelivered fails if not in shipped status', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, [1]);
    $fulfillment = $this->service->create($order, [$lines[0]->id => 1]);

    expect(fn () => $this->service->markAsDelivered($fulfillment))
        ->toThrow(InvalidArgumentException::class);
});

test('create fulfillment with non-existent line throws', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, [2]);

    expect(fn () => $this->service->create($order, [99999 => 1]))
        ->toThrow(InvalidArgumentException::class);
});
