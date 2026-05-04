<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Exceptions\InvalidFulfillmentOperationException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\FulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

/**
 * @return array{0: Order, 1: OrderLine, 2: OrderLine}
 */
function fulfillmentServiceOrder(FinancialStatus $financialStatus = FinancialStatus::Paid): array
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $firstProduct = Product::factory()->withDefaultVariant(2500)->create(['store_id' => $store->getKey()]);
    $secondProduct = Product::factory()->withDefaultVariant(1500)->create(['store_id' => $store->getKey()]);
    $firstVariant = ProductVariant::withoutGlobalScopes()->where('product_id', $firstProduct->getKey())->firstOrFail();
    $secondVariant = ProductVariant::withoutGlobalScopes()->where('product_id', $secondProduct->getKey())->firstOrFail();

    $order = Order::factory()->paid()->create([
        'store_id' => $store->getKey(),
        'financial_status' => $financialStatus,
        'status' => $financialStatus === FinancialStatus::Pending ? OrderStatus::Pending : OrderStatus::Paid,
    ]);
    $firstLine = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $firstProduct->getKey(),
        'variant_id' => $firstVariant->getKey(),
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);
    $secondLine = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $secondProduct->getKey(),
        'variant_id' => $secondVariant->getKey(),
        'quantity' => 1,
        'unit_price_amount' => 1500,
        'total_amount' => 1500,
    ]);

    return [$order, $firstLine, $secondLine];
}

test('fulfillment service blocks fulfillment until payment is confirmed', function () {
    [$order, $line] = fulfillmentServiceOrder(FinancialStatus::Pending);

    expect(fn () => app(FulfillmentService::class)->create($order, [$line->getKey() => 1]))
        ->toThrow(InvalidFulfillmentOperationException::class);
});

test('fulfillment service creates partial and complete fulfillments', function () {
    [$order, $firstLine, $secondLine] = fulfillmentServiceOrder();

    Event::fake();

    $firstFulfillment = app(FulfillmentService::class)->create($order, [
        $firstLine->getKey() => 2,
    ], [
        'tracking_company' => 'DHL',
        'tracking_number' => 'DHL123',
    ]);

    expect($firstFulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial)
        ->and($order->status)->toBe(OrderStatus::Paid);

    $secondFulfillment = app(FulfillmentService::class)->create($order, [
        $secondLine->getKey() => 1,
    ]);

    expect($secondFulfillment->lines)->toHaveCount(1)
        ->and($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(FulfillmentCreated::class, 2);
});

test('fulfillment service rejects over-fulfillment and invalid transitions', function () {
    [$order, $line] = fulfillmentServiceOrder();
    $fulfillment = app(FulfillmentService::class)->create($order, [$line->getKey() => 1]);

    expect(fn () => app(FulfillmentService::class)->create($order, [$line->getKey() => 2]))
        ->toThrow(InvalidFulfillmentOperationException::class);

    expect(fn () => app(FulfillmentService::class)->markDelivered($fulfillment))
        ->toThrow(InvalidFulfillmentOperationException::class);
});

test('fulfillment service marks shipments as shipped and delivered', function () {
    [$order, $line] = fulfillmentServiceOrder();
    $fulfillment = app(FulfillmentService::class)->create($order, [$line->getKey() => 1]);

    Event::fake();

    $shipped = app(FulfillmentService::class)->markShipped($fulfillment);
    $delivered = app(FulfillmentService::class)->markDelivered($shipped);

    expect($shipped->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($shipped->shipped_at)->not->toBeNull()
        ->and($delivered->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($delivered->delivered_at)->not->toBeNull();

    Event::assertDispatched(FulfillmentShipped::class);
    Event::assertDispatched(FulfillmentDelivered::class);
});
