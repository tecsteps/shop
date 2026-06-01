<?php

use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(RefundService::class);
});

/**
 * A paid order with one line and a captured payment.
 */
function paidOrder(int $total = 5000, int $quantity = 1, int $onHand = 10): Order
{
    $store = app('current_store');
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => intdiv($total, max($quantity, 1))]);
    $variant->inventoryItem->update(['quantity_on_hand' => $onHand, 'policy' => 'continue']);

    $order = Order::factory()->for($store)->paid()->create(['total_amount' => $total]);
    OrderLine::factory()->for($order)->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => intdiv($total, max($quantity, 1)),
        'total_amount' => $total,
    ]);
    Payment::factory()->for($order)->create(['amount' => $total, 'status' => 'captured']);

    return $order->load('lines.variant.inventoryItem', 'payments');
}

it('creates a full refund', function () {
    $order = paidOrder(5000);

    $this->service->create($order, $order->payments->first(), 5000);

    expect($order->fresh()->financial_status->value)->toBe('refunded')
        ->and($order->fresh()->status->value)->toBe('refunded');
});

it('creates a partial refund', function () {
    $order = paidOrder(5000);

    $this->service->create($order, $order->payments->first(), 2000);

    expect($order->fresh()->financial_status->value)->toBe('partially_refunded');
});

it('rejects refund exceeding payment amount', function () {
    $order = paidOrder(5000);

    expect(fn () => $this->service->create($order, $order->payments->first(), 6000))
        ->toThrow(InvalidArgumentException::class);
});

it('restocks inventory when restock flag is true', function () {
    $order = paidOrder(total: 5000, quantity: 2, onHand: 10);
    $lineId = $order->lines->first()->id;
    $item = $order->lines->first()->variant->inventoryItem;

    $this->service->create($order, $order->payments->first(), 5000, restock: true, lines: [$lineId => 2]);

    expect($item->fresh()->quantity_on_hand)->toBe(12);
});

it('does not restock when restock flag is false', function () {
    $order = paidOrder(total: 5000, quantity: 2, onHand: 10);
    $item = $order->lines->first()->variant->inventoryItem;

    $this->service->create($order, $order->payments->first(), 5000, restock: false);

    expect($item->fresh()->quantity_on_hand)->toBe(10);
});

it('records refund reason', function () {
    $order = paidOrder(5000);

    $refund = $this->service->create($order, $order->payments->first(), 1000, reason: 'Customer requested');

    expect($refund->reason)->toBe('Customer requested');
});

it('only allows admin or owner to process refunds', function () {
    $order = paidOrder(5000);

    $owner = $this->context['owner'];
    $staff = User::factory()->create();
    $this->store->users()->attach($staff->id, ['role' => StoreUserRole::Staff->value]);

    expect($owner->can('create', [Refund::class, $order]))->toBeTrue()
        ->and($staff->can('create', [Refund::class, $order]))->toBeFalse();
});
