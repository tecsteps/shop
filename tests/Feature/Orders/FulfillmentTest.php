<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FulfillmentService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->fulfillmentService = app(FulfillmentService::class);
});

function createPaidOrderWithLines($store, int $lineCount = 1, int $quantityPerLine = 2): array
{
    $order = Order::factory()->create([
        'store_id' => $store->id,
        'financial_status' => FinancialStatus::Paid,
        'status' => OrderStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    $lines = [];
    for ($i = 0; $i < $lineCount; $i++) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => 2500,
            'requires_shipping' => true,
        ]);

        $lines[] = OrderLine::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'title_snapshot' => $product->title,
            'quantity' => $quantityPerLine,
            'unit_price_amount' => 2500,
            'total_amount' => 2500 * $quantityPerLine,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ]);
    }

    return [$order, $lines];
}

it('creates a fulfillment with lines', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store);
    $orderLine = $lines[0];

    $fulfillment = $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);

    expect($fulfillment)->toBeInstanceOf(Fulfillment::class)
        ->and($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($fulfillment->lines)->toHaveCount(1)
        ->and($fulfillment->lines->first()->quantity)->toBe(2);
});

it('updates order fulfillment status to fulfilled when all lines are fulfilled', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store);
    $orderLine = $lines[0];

    $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);
});

it('updates order fulfillment status to partial when only some lines are fulfilled', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, lineCount: 2);

    $this->fulfillmentService->create($order, [
        $lines[0]->id => $lines[0]->quantity,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('sets partial fulfillment when only part of a line quantity is fulfilled', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, quantityPerLine: 4);
    $orderLine = $lines[0];

    $this->fulfillmentService->create($order, [
        $orderLine->id => 2,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('throws when requested quantity exceeds unfulfilled quantity', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store, quantityPerLine: 2);
    $orderLine = $lines[0];

    $this->fulfillmentService->create($order, [$orderLine->id => 2]);

    $order->refresh();
    $this->fulfillmentService->create($order, [$orderLine->id => 1]);
})->throws(\RuntimeException::class, 'remain unfulfilled');

it('throws when order line does not belong to the order', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store);

    $this->fulfillmentService->create($order, [99999 => 1]);
})->throws(\RuntimeException::class, 'not found on this order');

it('rejects fulfillment of pending (unpaid) orders', function () {
    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
    ]);
    $orderLine = OrderLine::query()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
        'tax_lines_json' => [],
        'discount_allocations_json' => [],
    ]);

    $this->fulfillmentService->create($order, [$orderLine->id => 1]);
})->throws(FulfillmentGuardException::class);

it('allows fulfillment of partially refunded orders', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::PartiallyRefunded,
        'status' => OrderStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    $orderLine = OrderLine::query()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
        'tax_lines_json' => [],
        'discount_allocations_json' => [],
    ]);

    $fulfillment = $this->fulfillmentService->create($order, [$orderLine->id => 1]);

    expect($fulfillment)->toBeInstanceOf(Fulfillment::class);
});

it('stores tracking information on fulfillment', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store);

    $fulfillment = $this->fulfillmentService->create($order, [
        $lines[0]->id => $lines[0]->quantity,
    ], [
        'tracking_company' => 'DHL',
        'tracking_number' => '1234567890',
        'tracking_url' => 'https://tracking.dhl.com/1234567890',
    ]);

    expect($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('1234567890')
        ->and($fulfillment->tracking_url)->toBe('https://tracking.dhl.com/1234567890');
});

it('marks a pending fulfillment as shipped', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store);

    $fulfillment = $this->fulfillmentService->create($order, [
        $lines[0]->id => $lines[0]->quantity,
    ]);

    $this->fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => 'TRACK123',
    ]);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($fulfillment->tracking_company)->toBe('DHL');
});

it('marks a shipped fulfillment as delivered', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store);

    $fulfillment = $this->fulfillmentService->create($order, [
        $lines[0]->id => $lines[0]->quantity,
    ]);
    $this->fulfillmentService->markAsShipped($fulfillment);

    $fulfillment->refresh();
    $this->fulfillmentService->markAsDelivered($fulfillment);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('rejects marking a delivered fulfillment as shipped', function () {
    [$order, $lines] = createPaidOrderWithLines($this->store);

    $fulfillment = $this->fulfillmentService->create($order, [
        $lines[0]->id => $lines[0]->quantity,
    ]);
    $this->fulfillmentService->markAsShipped($fulfillment);
    $fulfillment->refresh();
    $this->fulfillmentService->markAsDelivered($fulfillment);
    $fulfillment->refresh();

    $this->fulfillmentService->markAsShipped($fulfillment);
})->throws(\RuntimeException::class, 'Only pending fulfillments');
