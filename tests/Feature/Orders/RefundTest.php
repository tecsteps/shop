<?php

use App\Enums\FinancialStatus;
use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\StoreUser;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->refundService = app(RefundService::class);
});

/**
 * A paid order with a captured payment for the given total.
 *
 * @return array{order: Order, payment: Payment}
 */
function refundableOrder($test, int $total = 5000): array
{
    $order = Order::factory()->paid()->totaling($total)->for($test->store)->create();
    $payment = Payment::factory()->captured()->for($order)->create(['amount' => $total]);

    return ['order' => $order, 'payment' => $payment];
}

it('creates a full refund', function () {
    ['order' => $order, 'payment' => $payment] = refundableOrder($this);

    $refund = $this->refundService->create($order, $payment, 5000);

    $this->assertDatabaseHas('refunds', [
        'id' => $refund->getKey(),
        'order_id' => $order->getKey(),
        'amount' => 5000,
        'status' => 'processed',
    ]);
    expect($order->refresh()->financial_status)->toBe(FinancialStatus::Refunded);
});

it('creates a partial refund', function () {
    ['order' => $order, 'payment' => $payment] = refundableOrder($this);

    $this->refundService->create($order, $payment, 2000);

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('rejects refund exceeding payment amount', function () {
    ['order' => $order, 'payment' => $payment] = refundableOrder($this);

    $this->refundService->create($order, $payment, 6000);
})->throws(ValidationException::class);

it('restocks inventory when restock flag is true', function () {
    ['order' => $order, 'payment' => $payment] = refundableOrder($this);

    $variant = createPurchasableVariant($this->store, quantityOnHand: 5);
    OrderLine::factory()->for($order)->forVariant($variant)->create(['quantity' => 2]);

    $this->refundService->create($order, $payment, 5000, restock: true);

    expect($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(7);
});

it('does not restock when restock flag is false', function () {
    ['order' => $order, 'payment' => $payment] = refundableOrder($this);

    $variant = createPurchasableVariant($this->store, quantityOnHand: 5);
    OrderLine::factory()->for($order)->forVariant($variant)->create(['quantity' => 2]);

    $this->refundService->create($order, $payment, 5000, restock: false);

    expect($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(5);
});

it('only allows admin or owner to process refunds', function () {
    ['order' => $order] = refundableOrder($this);

    $staff = User::factory()->create();
    StoreUser::query()->create([
        'store_id' => $this->store->getKey(),
        'user_id' => $staff->getKey(),
        'role' => StoreUserRole::Staff,
    ]);

    expect(Gate::forUser($staff)->denies('createRefund', $order))->toBeTrue();
    expect(Gate::forUser($this->context['user'])->allows('createRefund', $order))->toBeTrue();
});

it('records refund reason', function () {
    ['order' => $order, 'payment' => $payment] = refundableOrder($this);

    $refund = $this->refundService->create($order, $payment, 5000, 'Customer requested');

    expect($refund->reason)->toBe('Customer requested');
});
