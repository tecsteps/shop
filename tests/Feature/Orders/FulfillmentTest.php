<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\StoreUserRole;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\StoreUser;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->fulfillmentService = app(FulfillmentService::class);
});

/**
 * A paid order with two lines (qty 3 and qty 2).
 *
 * @return array{order: Order, lineA: OrderLine, lineB: OrderLine}
 */
function fulfillableOrder($test): array
{
    $order = Order::factory()->paid()->for($test->store)->create();
    $lineA = OrderLine::factory()->for($order)->create(['quantity' => 3]);
    $lineB = OrderLine::factory()->for($order)->create(['quantity' => 2]);

    return ['order' => $order, 'lineA' => $lineA, 'lineB' => $lineB];
}

it('creates a fulfillment for specific order lines', function () {
    ['order' => $order, 'lineA' => $lineA] = fulfillableOrder($this);

    $fulfillment = $this->fulfillmentService->create($order, [$lineA->getKey() => 3]);

    $this->assertDatabaseHas('fulfillments', [
        'id' => $fulfillment->getKey(),
        'order_id' => $order->getKey(),
        'status' => 'pending',
    ]);
    expect($fulfillment->lines)->toHaveCount(1);
    expect($fulfillment->lines->first()->order_line_id)->toBe($lineA->getKey());
});

it('updates order fulfillment status to partial', function () {
    ['order' => $order, 'lineA' => $lineA] = fulfillableOrder($this);

    $this->fulfillmentService->create($order, [$lineA->getKey() => 3]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('updates order fulfillment status to fulfilled when all lines done', function () {
    ['order' => $order, 'lineA' => $lineA, 'lineB' => $lineB] = fulfillableOrder($this);

    $this->fulfillmentService->create($order, [$lineA->getKey() => 3]);
    $this->fulfillmentService->create($order, [$lineB->getKey() => 2]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('adds tracking information', function () {
    ['order' => $order, 'lineA' => $lineA] = fulfillableOrder($this);

    $fulfillment = $this->fulfillmentService->create($order, [$lineA->getKey() => 3]);

    $this->fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
    ]);

    $fulfillment->refresh();
    expect($fulfillment->tracking_company)->toBe('DHL');
    expect($fulfillment->tracking_number)->toBe('123456');
    expect($fulfillment->shipped_at)->not->toBeNull();
});

it('transitions fulfillment from pending to shipped', function () {
    ['order' => $order, 'lineA' => $lineA] = fulfillableOrder($this);

    $fulfillment = $this->fulfillmentService->create($order, [$lineA->getKey() => 3]);
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);

    $this->fulfillmentService->markAsShipped($fulfillment);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Shipped);
});

it('transitions fulfillment from shipped to delivered', function () {
    ['order' => $order, 'lineA' => $lineA] = fulfillableOrder($this);

    $fulfillment = $this->fulfillmentService->create($order, [$lineA->getKey() => 3]);
    $this->fulfillmentService->markAsShipped($fulfillment);

    $this->fulfillmentService->markAsDelivered($fulfillment);

    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
    expect($fulfillment->delivered_at)->not->toBeNull();
});

it('prevents fulfilling more than ordered quantity', function () {
    ['order' => $order, 'lineB' => $lineB] = fulfillableOrder($this);

    $this->fulfillmentService->create($order, [$lineB->getKey() => 3]);
})->throws(ValidationException::class);

it('fulfillment guard blocks fulfillment when financial_status is pending', function () {
    $order = Order::factory()->pending()->for($this->store)->create();
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);

    $this->fulfillmentService->create($order, [$line->getKey() => 1]);
})->throws(FulfillmentGuardException::class);

it('fulfillment guard allows fulfillment when financial_status is paid', function () {
    ['order' => $order, 'lineA' => $lineA] = fulfillableOrder($this);

    $fulfillment = $this->fulfillmentService->create($order, [$lineA->getKey() => 1]);

    expect($fulfillment->exists)->toBeTrue();
});

it('fulfillment guard allows fulfillment when financial_status is partially_refunded', function () {
    $order = Order::factory()->paid()->for($this->store)->create([
        'financial_status' => FinancialStatus::PartiallyRefunded,
    ]);
    $line = OrderLine::factory()->for($order)->create(['quantity' => 1]);

    $fulfillment = $this->fulfillmentService->create($order, [$line->getKey() => 1]);

    expect($fulfillment->exists)->toBeTrue();
});

it('auto-fulfills digital products on payment confirmation', function () {
    $checkout = createPaymentSelectedCheckout(
        $this->store,
        'bank_transfer',
        variantAttributes: ['requires_shipping' => false],
    );

    $order = app(CheckoutService::class)->completeCheckout($checkout);
    expect($order->financial_status)->toBe(FinancialStatus::Pending);
    expect($order->fulfillments)->toHaveCount(0);

    app(OrderService::class)->confirmBankTransferPayment($order);

    $order->refresh();
    $fulfillment = $order->fulfillments->first();
    expect($fulfillment)->not->toBeNull();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('only allows admin, owner, or staff to create fulfillments', function () {
    ['order' => $order] = fulfillableOrder($this);

    $support = User::factory()->create();
    StoreUser::query()->create([
        'store_id' => $this->store->getKey(),
        'user_id' => $support->getKey(),
        'role' => StoreUserRole::Support,
    ]);

    $staff = User::factory()->create();
    StoreUser::query()->create([
        'store_id' => $this->store->getKey(),
        'user_id' => $staff->getKey(),
        'role' => StoreUserRole::Staff,
    ]);

    expect(Gate::forUser($support)->denies('createFulfillment', $order))->toBeTrue();
    expect(Gate::forUser($staff)->allows('createFulfillment', $order))->toBeTrue();
});
