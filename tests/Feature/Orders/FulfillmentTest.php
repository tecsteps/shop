<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\StoreUserRole;
use App\Exceptions\FulfillmentGuardException;
use App\Models\StoreUser;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Tests\Support\CatalogTestHelper;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
});

function fulfillmentOrderContext(array $lines = [['price' => 2500, 'qty' => 3], ['price' => 1000, 'qty' => 2]]): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, $lines);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 0);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    return ['store' => $store, 'cart' => $cart, 'order' => $order];
}

it('creates a fulfillment for specific order lines', function () {
    $order = fulfillmentOrderContext()['order'];
    $line = $order->orderLines()->first();

    $fulfillment = app(FulfillmentService::class)->create($order, [$line->id => 3]);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);
    expect($fulfillment->fulfillmentLines()->count())->toBe(1);
});

it('updates order fulfillment status to partial', function () {
    $order = fulfillmentOrderContext()['order'];
    $line = $order->orderLines()->first();

    app(FulfillmentService::class)->create($order, [$line->id => 3]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('updates order fulfillment status to fulfilled when all lines done', function () {
    $order = fulfillmentOrderContext()['order'];
    $lines = [];

    foreach ($order->orderLines()->get() as $line) {
        $lines[$line->id] = $line->quantity;
    }

    app(FulfillmentService::class)->create($order, $lines);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->refresh()->status)->toBe(OrderStatus::Fulfilled);
});

it('adds tracking information', function () {
    $order = fulfillmentOrderContext()['order'];
    $line = $order->orderLines()->first();

    $fulfillment = app(FulfillmentService::class)->create($order, [$line->id => 3], [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
        'tracking_url' => 'https://dhl.test/123456',
    ]);
    $fulfillment = app(FulfillmentService::class)->markAsShipped($fulfillment);

    expect($fulfillment->tracking_company)->toBe('DHL');
    expect($fulfillment->tracking_number)->toBe('123456');
    expect($fulfillment->shipped_at)->not->toBeNull();
});

it('transitions fulfillment from pending to shipped', function () {
    $order = fulfillmentOrderContext()['order'];
    $line = $order->orderLines()->first();

    $fulfillment = app(FulfillmentService::class)->create($order, [$line->id => 3]);
    $fulfillment = app(FulfillmentService::class)->markAsShipped($fulfillment);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped);
});

it('transitions fulfillment from shipped to delivered', function () {
    $order = fulfillmentOrderContext()['order'];
    $line = $order->orderLines()->first();

    $fulfillment = app(FulfillmentService::class)->create($order, [$line->id => 3]);
    $fulfillment = app(FulfillmentService::class)->markAsShipped($fulfillment);
    $fulfillment = app(FulfillmentService::class)->markAsDelivered($fulfillment);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('prevents fulfilling more than ordered quantity', function () {
    $order = fulfillmentOrderContext()['order'];
    $line = $order->orderLines()->first();

    app(FulfillmentService::class)->create($order, [$line->id => $line->quantity + 1]);
})->throws(Illuminate\Validation\ValidationException::class);

it('fulfillment guard blocks fulfillment when financial status is pending', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::BankTransfer);
    $order = $checkouts->pay($checkout, PaymentMethod::BankTransfer, []);

    $line = $order->orderLines()->first();

    app(FulfillmentService::class)->create($order, [$line->id => 1]);
})->throws(FulfillmentGuardException::class);

it('fulfillment guard allows fulfillment when financial status is paid', function () {
    $order = fulfillmentOrderContext()['order'];
    $line = $order->orderLines()->first();

    $fulfillment = app(FulfillmentService::class)->create($order, [$line->id => 1]);

    expect($fulfillment->exists)->toBeTrue();
});

it('fulfillment guard allows fulfillment when financial status is partially refunded', function () {
    $ctx = fulfillmentOrderContext();
    $order = $ctx['order'];

    app(RefundService::class)->create($order, 500);

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $line = $order->orderLines()->first();
    $fulfillment = app(FulfillmentService::class)->create($order->refresh(), [$line->id => 1]);

    expect($fulfillment->exists)->toBeTrue();
});

it('auto-fulfills digital products on payment confirmation', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 1, 'shipping' => false]]);
    $rate = CommerceTestHelper::flatShippingRate($store);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, null);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $fulfillment = $order->fulfillments()->first();

    expect($fulfillment)->not->toBeNull();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('only allows admin owner or staff to create fulfillments', function () {
    ['store' => $store] = $this->createStoreContext();

    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 0);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    $support = App\Models\User::factory()->create();
    StoreUser::factory()->create(['store_id' => $store->id, 'user_id' => $support->id, 'role' => StoreUserRole::Support]);

    $line = $order->orderLines()->first();

    $response = $this->postJson(
        "/api/admin/v1/stores/{$store->id}/orders/{$order->id}/fulfillments",
        ['line_items' => [['order_line_id' => $line->id, 'quantity' => 1]]],
        CatalogTestHelper::adminHeaders($support, ['write-orders'])
    );

    $response->assertForbidden();
});
