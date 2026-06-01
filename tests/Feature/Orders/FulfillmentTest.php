<?php

use App\Actions\Orders\ConfirmBankTransferPayment;
use App\Enums\StoreUserRole;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\FulfillmentService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(FulfillmentService::class);
});

/**
 * A paid order with two physical lines (qty 3 and qty 2).
 */
function fulfillableOrder(string $financialStatus = 'paid', bool $digital = false): Order
{
    $store = app('current_store');
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);

    $order = Order::factory()->for($store)->create([
        'status' => $financialStatus === 'pending' ? 'pending' : 'paid',
        'financial_status' => $financialStatus,
        'total_amount' => 5000,
    ]);

    foreach ([3, 2] as $qty) {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'requires_shipping' => ! $digital,
            'price_amount' => 1000,
        ]);
        OrderLine::factory()->for($order)->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity' => $qty,
            'unit_price_amount' => 1000,
            'total_amount' => 1000 * $qty,
        ]);
    }

    return $order->load('lines.variant');
}

it('creates a fulfillment for specific order lines', function () {
    $order = fulfillableOrder();
    $line = $order->lines->first();

    $fulfillment = $this->service->create($order, [$line->id => 3]);

    expect($fulfillment->lines)->toHaveCount(1)
        ->and($fulfillment->lines->first()->quantity)->toBe(3);
});

it('updates order fulfillment status to partial', function () {
    $order = fulfillableOrder();
    $line = $order->lines->first();

    $this->service->create($order, [$line->id => 3]);

    expect($order->fresh()->fulfillment_status->value)->toBe('partial');
});

it('updates order fulfillment status to fulfilled when all lines done', function () {
    $order = fulfillableOrder();
    [$lineA, $lineB] = [$order->lines[0], $order->lines[1]];

    $this->service->create($order, [$lineA->id => 3]);
    $this->service->create($order->fresh('lines.fulfillmentLines'), [$lineB->id => 2]);

    expect($order->fresh()->fulfillment_status->value)->toBe('fulfilled')
        ->and($order->fresh()->status->value)->toBe('fulfilled');
});

it('adds tracking information', function () {
    $order = fulfillableOrder();
    $fulfillment = $this->service->create($order, [$order->lines->first()->id => 1]);

    $this->service->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
    ]);

    $fresh = $fulfillment->fresh();
    expect($fresh->tracking_company)->toBe('DHL')
        ->and($fresh->tracking_number)->toBe('123456')
        ->and($fresh->shipped_at)->not->toBeNull();
});

it('transitions fulfillment from pending to shipped', function () {
    $order = fulfillableOrder();
    $fulfillment = $this->service->create($order, [$order->lines->first()->id => 1]);

    $this->service->markAsShipped($fulfillment);

    expect($fulfillment->fresh()->status->value)->toBe('shipped');
});

it('transitions fulfillment from shipped to delivered', function () {
    $order = fulfillableOrder();
    $fulfillment = $this->service->create($order, [$order->lines->first()->id => 1]);
    $this->service->markAsShipped($fulfillment);

    $this->service->markAsDelivered($fulfillment->fresh());

    expect($fulfillment->fresh()->status->value)->toBe('delivered')
        ->and($fulfillment->fresh()->delivered_at)->not->toBeNull();
});

it('prevents fulfilling more than ordered quantity', function () {
    $order = fulfillableOrder();
    $line = $order->lines->firstWhere('quantity', 2);

    expect(fn () => $this->service->create($order, [$line->id => 3]))
        ->toThrow(FulfillmentGuardException::class);
});

it('fulfillment guard blocks fulfillment when financial_status is pending', function () {
    $order = fulfillableOrder(financialStatus: 'pending');

    expect(fn () => $this->service->create($order, [$order->lines->first()->id => 1]))
        ->toThrow(FulfillmentGuardException::class);
});

it('fulfillment guard allows fulfillment when financial_status is paid', function () {
    $order = fulfillableOrder(financialStatus: 'paid');

    $fulfillment = $this->service->create($order, [$order->lines->first()->id => 1]);

    expect($fulfillment->exists)->toBeTrue();
});

it('fulfillment guard allows fulfillment when financial_status is partially_refunded', function () {
    $order = fulfillableOrder(financialStatus: 'partially_refunded');

    $fulfillment = $this->service->create($order, [$order->lines->first()->id => 1]);

    expect($fulfillment->exists)->toBeTrue();
});

it('auto-fulfills digital products on payment confirmation', function () {
    // Bank-transfer pending order with only digital items; confirm payment.
    $store = $this->store;
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'requires_shipping' => false, 'price_amount' => 5000]);
    $variant->inventoryItem->update(['quantity_on_hand' => 10, 'quantity_reserved' => 1, 'policy' => 'continue']);

    $order = Order::factory()->for($store)->bankTransfer()->create(['total_amount' => 5000]);
    OrderLine::factory()->for($order)->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'total_amount' => 5000,
    ]);
    Payment::factory()->for($order)->pending()->create(['amount' => 5000]);

    app(ConfirmBankTransferPayment::class)->handle($order->load('lines.variant.inventoryItem'));

    $fulfillment = $order->fresh()->fulfillments->first();

    expect($order->fresh()->fulfillment_status->value)->toBe('fulfilled')
        ->and($fulfillment->status->value)->toBe('delivered');
});

it('only allows admin, owner, or staff to create fulfillments', function () {
    $order = fulfillableOrder();

    $staff = User::factory()->create();
    $this->store->users()->attach($staff->id, ['role' => StoreUserRole::Staff->value]);

    $support = User::factory()->create();
    $this->store->users()->attach($support->id, ['role' => StoreUserRole::Support->value]);

    expect($staff->can('create', [Fulfillment::class, $order]))->toBeTrue()
        ->and($support->can('create', [Fulfillment::class, $order]))->toBeFalse();
});
