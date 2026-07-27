<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Paid order with two lines (qty 3 and qty 2).
 *
 * @return array{0: Store, 1: Order}
 */
function fulfillmentOrder(array $attributes = []): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $order = Order::factory()->paid()->withLines([
        ['quantity' => 3, 'unit_price_amount' => 1000],
        ['quantity' => 2, 'unit_price_amount' => 500],
    ])->create(array_merge(['store_id' => $store->id], $attributes));

    return [$store, $order->refresh()];
}

test('creates a fulfillment for specific order lines', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne, $lineTwo] = $order->lines->values();

    Event::fake([FulfillmentCreated::class]);

    $fulfillment = app(FulfillmentService::class)->create($order, [$lineOne->id => 3]);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($fulfillment->lines)->toHaveCount(1)
        ->and($fulfillment->lines->first()->order_line_id)->toBe($lineOne->id)
        ->and($fulfillment->lines->first()->quantity)->toBe(3);

    Event::assertDispatched(FulfillmentCreated::class, fn (FulfillmentCreated $event): bool => $event->fulfillment->id === $fulfillment->id);
});

test('updates order fulfillment status to partial', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne, $lineTwo] = $order->lines->values();

    app(FulfillmentService::class)->create($order, [$lineOne->id => 3]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentOrderStatus::Partial)
        ->and($order->status)->toBe(OrderStatus::Paid);
});

test('updates order fulfillment status to fulfilled when all lines done', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne, $lineTwo] = $order->lines->values();

    $service = app(FulfillmentService::class);
    $service->create($order, [$lineOne->id => 3]);

    Event::fake([OrderFulfilled::class]);

    $service->create($order->refresh(), [$lineTwo->id => 2]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    Event::assertDispatched(OrderFulfilled::class, fn (OrderFulfilled $event): bool => $event->order->id === $order->id);
});

test('adds tracking information', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne] = $order->lines->values();

    $fulfillment = app(FulfillmentService::class)->create($order, [$lineOne->id => 1], [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
        'tracking_url' => 'https://dhl.example/track/123456',
    ]);

    expect($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('123456')
        ->and($fulfillment->tracking_url)->toBe('https://dhl.example/track/123456');
});

test('transitions fulfillment from pending to shipped', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne] = $order->lines->values();

    $fulfillment = app(FulfillmentService::class)->create($order, [$lineOne->id => 1]);

    Event::fake([FulfillmentShipped::class]);

    app(FulfillmentService::class)->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
    ]);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($fulfillment->tracking_number)->toBe('123456');

    Event::assertDispatched(FulfillmentShipped::class);
});

test('transitions fulfillment from shipped to delivered', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne] = $order->lines->values();

    $service = app(FulfillmentService::class);
    $fulfillment = $service->create($order, [$lineOne->id => 1]);
    $service->markAsShipped($fulfillment);

    Event::fake([FulfillmentDelivered::class]);

    $service->markAsDelivered($fulfillment->refresh());

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Delivered);

    Event::assertDispatched(FulfillmentDelivered::class);
});

test('prevents fulfilling more than ordered quantity', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne] = $order->lines->values();

    app(FulfillmentService::class)->create($order, [$lineOne->id => 4]);
})->throws(ValidationException::class);

test('prevents fulfilling more than the remaining unfulfilled quantity', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne] = $order->lines->values();

    $service = app(FulfillmentService::class);
    $service->create($order, [$lineOne->id => 2]);
    $service->create($order->refresh(), [$lineOne->id => 2]);
})->throws(ValidationException::class);

test('fulfillment guard blocks fulfillment when financial status is pending', function () {
    [$store, $order] = fulfillmentOrder(['financial_status' => FinancialStatus::Pending]);
    [$lineOne] = $order->lines->values();

    app(FulfillmentService::class)->create($order, [$lineOne->id => 1]);
})->throws(FulfillmentGuardException::class);

test('fulfillment guard allows fulfillment when financial status is paid', function () {
    [$store, $order] = fulfillmentOrder();
    [$lineOne] = $order->lines->values();

    $fulfillment = app(FulfillmentService::class)->create($order, [$lineOne->id => 1]);

    expect($fulfillment)->toBeInstanceOf(App\Models\Fulfillment::class);
});

test('fulfillment guard allows fulfillment when financial status is partially refunded', function () {
    [$store, $order] = fulfillmentOrder(['financial_status' => FinancialStatus::PartiallyRefunded]);
    [$lineOne] = $order->lines->values();

    $fulfillment = app(FulfillmentService::class)->create($order, [$lineOne->id => 1]);

    expect($fulfillment)->toBeInstanceOf(App\Models\Fulfillment::class);
});

test('auto-fulfills digital products on payment confirmation', function () {
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'requires_shipping' => false,
    ]);
    ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);

    $service = app(CheckoutService::class);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, null);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');

    $order = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled);
    $fulfillment = $order->fulfillments->first();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

test('only allows admin, owner, or staff to create fulfillments', function () {
    [$store, $order] = fulfillmentOrder();

    $support = test()->createUserWithRole($store, 'support');
    $staff = test()->createUserWithRole($store, 'staff');

    expect(Gate::forUser($support)->inspect('createFulfillment', $order)->denied())->toBeTrue()
        ->and(Gate::forUser($staff)->inspect('createFulfillment', $order)->allowed())->toBeTrue();
});
