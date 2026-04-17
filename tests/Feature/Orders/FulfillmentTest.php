<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\FulfillmentDelivered;
use App\Exceptions\FulfillmentGuardException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BankTransferService;
use App\Services\FulfillmentService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->fulfillmentService = app(FulfillmentService::class);
});

it('creates a fulfillment for specific order lines', function () {
    $order = createFulfillableOrder($this->store);

    $fulfillment = $this->fulfillmentService->create($order, [
        $order->lines->first()->id => $order->lines->first()->quantity,
    ]);

    expect($fulfillment)->not->toBeNull()
        ->and($fulfillment->lines()->count())->toBe(1);
});

it('updates order fulfillment status to partial', function () {
    $order = createFulfillableOrder($this->store, lineCount: 2);
    $firstLine = $order->lines->first();

    $this->fulfillmentService->create($order, [
        $firstLine->id => $firstLine->quantity,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('updates order fulfillment status to fulfilled when all lines done', function () {
    $order = createFulfillableOrder($this->store);
    $lines = [];
    foreach ($order->lines as $line) {
        $lines[$line->id] = $line->quantity;
    }

    $this->fulfillmentService->create($order, $lines);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);
});

it('adds tracking information', function () {
    $order = createFulfillableOrder($this->store);
    $lines = [];
    foreach ($order->lines as $line) {
        $lines[$line->id] = $line->quantity;
    }

    $fulfillment = $this->fulfillmentService->create($order, $lines, [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
    ]);

    expect($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('123456');
});

it('transitions fulfillment from pending to shipped', function () {
    $order = createFulfillableOrder($this->store);
    $lines = [];
    foreach ($order->lines as $line) {
        $lines[$line->id] = $line->quantity;
    }

    $fulfillment = $this->fulfillmentService->create($order, $lines);
    $shipped = $this->fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
    ]);

    expect($shipped->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($shipped->shipped_at)->not->toBeNull();
});

it('transitions fulfillment from shipped to delivered', function () {
    $order = createFulfillableOrder($this->store);
    $lines = [];
    foreach ($order->lines as $line) {
        $lines[$line->id] = $line->quantity;
    }

    $fulfillment = $this->fulfillmentService->create($order, $lines);
    $shipped = $this->fulfillmentService->markAsShipped($fulfillment);
    $delivered = $this->fulfillmentService->markAsDelivered($shipped);

    expect($delivered->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('prevents fulfilling more than ordered quantity', function () {
    $order = createFulfillableOrder($this->store);
    $line = $order->lines->first();

    $this->fulfillmentService->create($order, [
        $line->id => $line->quantity + 1,
    ]);
})->throws(\InvalidArgumentException::class);

it('fulfillment guard blocks fulfillment when financial_status is pending', function () {
    $order = Order::factory()->bankTransfer()->create([
        'store_id' => $this->store->id,
    ]);
    OrderLine::factory()->create(['order_id' => $order->id, 'quantity' => 2]);
    $order->loadMissing('lines');

    $this->fulfillmentService->create($order, [
        $order->lines->first()->id => 1,
    ]);
})->throws(FulfillmentGuardException::class);

it('fulfillment guard allows fulfillment when financial_status is paid', function () {
    $order = createFulfillableOrder($this->store);
    $lines = [];
    foreach ($order->lines as $line) {
        $lines[$line->id] = $line->quantity;
    }

    $fulfillment = $this->fulfillmentService->create($order, $lines);

    expect($fulfillment)->not->toBeNull();
});

it('fulfillment guard allows fulfillment when financial_status is partially_refunded', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::PartiallyRefunded,
        'status' => OrderStatus::Paid,
    ]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);
    $order->loadMissing('lines');

    $fulfillment = $this->fulfillmentService->create($order, [
        $order->lines->first()->id => 1,
    ]);

    expect($fulfillment)->not->toBeNull();
});

it('auto-fulfills digital products on payment confirmation', function () {
    $order = Order::factory()->bankTransfer()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);
    Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'method' => PaymentMethod::BankTransfer,
    ]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 2,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $service = app(BankTransferService::class);
    $result = $service->confirmPayment($order);

    $result->refresh();
    expect($result->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($result->fulfillments()->count())->toBe(1);
});

it('dispatches FulfillmentDelivered on delivery', function () {
    Event::fake([FulfillmentDelivered::class]);

    $order = createFulfillableOrder($this->store);
    $lines = [];
    foreach ($order->lines as $line) {
        $lines[$line->id] = $line->quantity;
    }

    $fulfillment = $this->fulfillmentService->create($order, $lines);
    $shipped = $this->fulfillmentService->markAsShipped($fulfillment);
    $this->fulfillmentService->markAsDelivered($shipped);

    Event::assertDispatched(FulfillmentDelivered::class);
});

function createFulfillableOrder(\App\Models\Store $store, int $lineCount = 1): Order
{
    /** @var Order $order */
    $order = Order::factory()->paid()->create([
        'store_id' => $store->id,
    ]);

    for ($i = 0; $i < $lineCount; $i++) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    $order->loadMissing('lines');

    return $order;
}
