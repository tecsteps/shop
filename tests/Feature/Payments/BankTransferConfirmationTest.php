<?php

use App\Actions\Orders\ConfirmBankTransferPayment;
use App\Enums\PaymentMethod;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->confirm = app(ConfirmBankTransferPayment::class);
});

/**
 * Build a pending bank-transfer order with one reserved line.
 */
function pendingBankTransferOrder(bool $digital = false, int $placedDaysAgo = 0): Order
{
    $store = app('current_store');
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => ! $digital,
        'price_amount' => 5000,
    ]);
    $variant->inventoryItem->update(['quantity_on_hand' => 10, 'quantity_reserved' => 2, 'policy' => 'continue']);

    $order = Order::factory()->for($store)->bankTransfer()->create([
        'total_amount' => 10000,
        'placed_at' => Carbon::now()->subDays($placedDaysAgo),
    ]);
    OrderLine::factory()->for($order)->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'total_amount' => 10000,
    ]);
    Payment::factory()->for($order)->pending()->create(['amount' => 10000]);

    return $order->load('lines.variant.inventoryItem');
}

it('admin can confirm bank transfer payment', function () {
    $order = pendingBankTransferOrder();
    $item = $order->lines->first()->variant->inventoryItem;

    $this->confirm->handle($order);

    expect($order->fresh()->financial_status->value)->toBe('paid')
        ->and($order->fresh()->status->value)->toBe('paid')
        ->and($order->payments->first()->fresh()->status->value)->toBe('captured')
        ->and($item->fresh()->quantity_on_hand)->toBe(8)
        ->and($item->fresh()->quantity_reserved)->toBe(0);
});

it('cannot confirm payment for non-bank-transfer orders', function () {
    $order = Order::factory()->for($this->store)->paid()->create(['payment_method' => PaymentMethod::CreditCard->value]);

    expect(fn () => $this->confirm->handle($order))->toThrow(RuntimeException::class);
});

it('cannot confirm already confirmed payment', function () {
    $order = Order::factory()->for($this->store)->bankTransfer()->create(['financial_status' => 'paid']);

    expect(fn () => $this->confirm->handle($order))->toThrow(RuntimeException::class);
});

it('auto-cancel job cancels unpaid bank transfer orders after config days', function () {
    config(['shop.bank_transfer_cancel_days' => 7]);
    $order = pendingBankTransferOrder(placedDaysAgo: 8);
    $item = $order->lines->first()->variant->inventoryItem;

    app(CancelUnpaidBankTransferOrders::class)->handle(app(OrderService::class));

    expect($order->fresh()->status->value)->toBe('cancelled')
        ->and($order->fresh()->financial_status->value)->toBe('voided')
        ->and($item->fresh()->quantity_reserved)->toBe(0);
});

it('auto-cancel job does not cancel orders within config days', function () {
    config(['shop.bank_transfer_cancel_days' => 7]);
    $order = pendingBankTransferOrder(placedDaysAgo: 2);

    app(CancelUnpaidBankTransferOrders::class)->handle(app(OrderService::class));

    expect($order->fresh()->status->value)->toBe('pending');
});

it('auto-fulfills digital products on payment confirmation', function () {
    $order = pendingBankTransferOrder(digital: true);

    $this->confirm->handle($order);

    $fulfillment = $order->fresh()->fulfillments->first();

    expect($order->fresh()->fulfillment_status->value)->toBe('fulfilled')
        ->and($fulfillment)->not->toBeNull()
        ->and($fulfillment->status->value)->toBe('delivered');
});
