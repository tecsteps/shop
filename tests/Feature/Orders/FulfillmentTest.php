<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\FulfillmentService;
use Illuminate\Support\Facades\Event;

function createFulfillmentTestContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#1001',
        'payment_method' => PaymentMethod::CreditCard,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'currency' => 'EUR',
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    $line1 = OrderLine::create([
        'order_id' => $order->id,
        'title_snapshot' => 'Product A',
        'price_amount' => 2500,
        'quantity' => 2,
        'total_amount' => 5000,
        'requires_shipping' => true,
    ]);

    $line2 = OrderLine::create([
        'order_id' => $order->id,
        'title_snapshot' => 'Product B',
        'price_amount' => 1000,
        'quantity' => 3,
        'total_amount' => 3000,
        'requires_shipping' => true,
    ]);

    return array_merge($ctx, compact('order', 'line1', 'line2'));
}

it('creates a fulfillment for all lines', function () {
    $ctx = createFulfillmentTestContext();
    Event::fake([OrderFulfilled::class]);
    $service = app(FulfillmentService::class);

    $fulfillment = $service->create($ctx['order'], [
        $ctx['line1']->id => 2,
        $ctx['line2']->id => 3,
    ], [
        'tracking_company' => 'DHL',
        'tracking_number' => '1234567890',
    ]);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('1234567890')
        ->and($fulfillment->lines)->toHaveCount(2);

    $ctx['order']->refresh();
    expect($ctx['order']->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($ctx['order']->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class);
});

it('creates partial fulfillment', function () {
    $ctx = createFulfillmentTestContext();
    $service = app(FulfillmentService::class);

    $service->create($ctx['order'], [
        $ctx['line1']->id => 1,
    ]);

    $ctx['order']->refresh();
    expect($ctx['order']->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('blocks fulfillment when financial status is pending', function () {
    $ctx = createFulfillmentTestContext();
    $ctx['order']->update(['financial_status' => FinancialStatus::Pending]);
    $service = app(FulfillmentService::class);

    expect(fn () => $service->create($ctx['order']->fresh(), [
        $ctx['line1']->id => 2,
    ]))->toThrow(FulfillmentGuardException::class);
});

it('blocks fulfillment when financial status is voided', function () {
    $ctx = createFulfillmentTestContext();
    $ctx['order']->update(['financial_status' => FinancialStatus::Voided]);
    $service = app(FulfillmentService::class);

    expect(fn () => $service->create($ctx['order']->fresh(), [
        $ctx['line1']->id => 2,
    ]))->toThrow(FulfillmentGuardException::class);
});

it('blocks fulfillment when financial status is refunded', function () {
    $ctx = createFulfillmentTestContext();
    $ctx['order']->update(['financial_status' => FinancialStatus::Refunded]);
    $service = app(FulfillmentService::class);

    expect(fn () => $service->create($ctx['order']->fresh(), [
        $ctx['line1']->id => 2,
    ]))->toThrow(FulfillmentGuardException::class);
});

it('allows fulfillment when financial status is partially refunded', function () {
    $ctx = createFulfillmentTestContext();
    $ctx['order']->update(['financial_status' => FinancialStatus::PartiallyRefunded]);
    $service = app(FulfillmentService::class);

    $fulfillment = $service->create($ctx['order']->fresh(), [
        $ctx['line1']->id => 2,
    ]);

    expect($fulfillment)->not->toBeNull();
});

it('rejects over-fulfillment', function () {
    $ctx = createFulfillmentTestContext();
    $service = app(FulfillmentService::class);

    expect(fn () => $service->create($ctx['order'], [
        $ctx['line1']->id => 5,
    ]))->toThrow(InvalidArgumentException::class);
});

it('marks fulfillment as shipped', function () {
    $ctx = createFulfillmentTestContext();
    $service = app(FulfillmentService::class);

    $fulfillment = $service->create($ctx['order'], [$ctx['line1']->id => 2]);

    $service->markAsShipped($fulfillment, [
        'tracking_company' => 'UPS',
        'tracking_number' => 'UPS123',
        'tracking_url' => 'https://ups.com/track/UPS123',
    ]);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($fulfillment->tracking_company)->toBe('UPS');
});

it('marks fulfillment as delivered', function () {
    $ctx = createFulfillmentTestContext();
    $service = app(FulfillmentService::class);

    $fulfillment = $service->create($ctx['order'], [$ctx['line1']->id => 2]);
    $service->markAsShipped($fulfillment);
    $service->markAsDelivered($fulfillment);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->delivered_at)->not->toBeNull();
});

it('handles multiple partial fulfillments to full fulfillment', function () {
    $ctx = createFulfillmentTestContext();
    Event::fake([OrderFulfilled::class]);
    $service = app(FulfillmentService::class);

    $service->create($ctx['order'], [$ctx['line1']->id => 1]);
    expect($ctx['order']->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);

    $service->create($ctx['order']->fresh(), [
        $ctx['line1']->id => 1,
        $ctx['line2']->id => 3,
    ]);
    expect($ctx['order']->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class);
});
