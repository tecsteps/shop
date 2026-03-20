<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FulfillmentService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->fulfillmentService = app(FulfillmentService::class);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $this->product = Product::factory()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'requires_shipping' => true,
    ]);

    $this->order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'total_amount' => 5000,
    ]);

    $this->line1 = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'variant_id' => $this->variant->id,
        'quantity' => 3,
        'unit_price_amount' => 1000,
        'total_amount' => 3000,
    ]);

    $this->line2 = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 1000,
        'total_amount' => 2000,
    ]);

    Payment::factory()->create([
        'order_id' => $this->order->id,
        'amount' => 5000,
    ]);
});

it('creates a fulfillment for all order lines', function () {
    Event::fake();

    $fulfillment = $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 3, $this->line2->id => 2],
        ['tracking_company' => 'DHL', 'tracking_number' => 'TRACK123'],
    );

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('TRACK123')
        ->and($fulfillment->fulfillmentLines)->toHaveCount(2);

    $this->order->refresh();
    expect($this->order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($this->order->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(FulfillmentCreated::class);
    Event::assertDispatched(OrderFulfilled::class);
});

it('creates a partial fulfillment', function () {
    $fulfillment = $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 2],
    );

    expect($fulfillment->fulfillmentLines)->toHaveCount(1);

    $this->order->refresh();
    expect($this->order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('prevents fulfillment when financial status is pending', function () {
    $this->order->update(['financial_status' => FinancialStatus::Pending]);

    expect(fn () => $this->fulfillmentService->create(
        $this->order->fresh(),
        [$this->line1->id => 1],
    ))->toThrow(FulfillmentGuardException::class);
});

it('prevents fulfillment when financial status is voided', function () {
    $this->order->update([
        'financial_status' => FinancialStatus::Voided,
        'status' => OrderStatus::Cancelled,
    ]);

    expect(fn () => $this->fulfillmentService->create(
        $this->order->fresh(),
        [$this->line1->id => 1],
    ))->toThrow(FulfillmentGuardException::class);
});

it('allows fulfillment when financial status is partially_refunded', function () {
    $this->order->update(['financial_status' => FinancialStatus::PartiallyRefunded]);

    $fulfillment = $this->fulfillmentService->create(
        $this->order->fresh(),
        [$this->line1->id => 1],
    );

    expect($fulfillment)->not->toBeNull();
});

it('prevents over-fulfillment of order lines', function () {
    // Fulfill 2 of 3 units from line1
    $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 2],
    );

    // Attempt to fulfill 2 more (only 1 remaining)
    expect(fn () => $this->fulfillmentService->create(
        $this->order->fresh(),
        [$this->line1->id => 2],
    ))->toThrow(RuntimeException::class, 'Only 1 remaining');
});

it('allows multiple partial fulfillments until fully fulfilled', function () {
    // First fulfillment: 2 of line1
    $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 2],
    );

    $this->order->refresh();
    expect($this->order->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    // Second fulfillment: remaining line1 + all line2
    $this->fulfillmentService->create(
        $this->order->fresh(),
        [$this->line1->id => 1, $this->line2->id => 2],
    );

    $this->order->refresh();
    expect($this->order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($this->order->status)->toBe(OrderStatus::Fulfilled);
});

it('marks fulfillment as shipped', function () {
    Event::fake();

    $fulfillment = $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 3, $this->line2->id => 2],
    );

    $this->fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'UPS',
        'tracking_number' => 'UPS-789',
    ]);

    $fulfillment->refresh();

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->tracking_company)->toBe('UPS')
        ->and($fulfillment->tracking_number)->toBe('UPS-789')
        ->and($fulfillment->shipped_at)->not->toBeNull();

    Event::assertDispatched(FulfillmentShipped::class);
});

it('marks fulfillment as delivered', function () {
    $fulfillment = $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 3, $this->line2->id => 2],
    );

    $this->fulfillmentService->markAsShipped($fulfillment);
    $this->fulfillmentService->markAsDelivered($fulfillment->fresh());

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->delivered_at)->not->toBeNull();
});

it('prevents shipping a non-pending fulfillment', function () {
    $fulfillment = $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 1],
    );

    $this->fulfillmentService->markAsShipped($fulfillment);

    expect(fn () => $this->fulfillmentService->markAsShipped($fulfillment->fresh()))
        ->toThrow(RuntimeException::class, 'Only pending fulfillments');
});

it('prevents delivering a non-shipped fulfillment', function () {
    $fulfillment = $this->fulfillmentService->create(
        $this->order,
        [$this->line1->id => 1],
    );

    expect(fn () => $this->fulfillmentService->markAsDelivered($fulfillment))
        ->toThrow(RuntimeException::class, 'Only shipped fulfillments');
});

it('prevents fulfilling an order line that does not belong to the order', function () {
    $otherOrder = Order::factory()->paid()->create(['store_id' => $this->store->id]);
    $otherLine = OrderLine::factory()->create([
        'order_id' => $otherOrder->id,
        'quantity' => 1,
    ]);

    expect(fn () => $this->fulfillmentService->create(
        $this->order,
        [$otherLine->id => 1],
    ))->toThrow(RuntimeException::class, 'does not belong to this order');
});
