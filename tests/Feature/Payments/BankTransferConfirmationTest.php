<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->orderService = app(OrderService::class);
});

function createBankTransferOrder($store, array $options = []): Order
{
    $requiresShipping = $options['requires_shipping'] ?? true;

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'requires_shipping' => $requiresShipping,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 1,
        'policy' => InventoryPolicy::Deny,
    ]);

    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => (string) fake()->unique()->numberBetween(4000, 99999),
        'email' => 'bank@example.com',
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_method' => PaymentMethod::BankTransfer,
        'total_amount' => 5000,
        'subtotal_amount' => 5000,
        'placed_at' => $options['placed_at'] ?? now(),
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
        'requires_shipping' => $requiresShipping,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'method' => PaymentMethod::BankTransfer,
        'provider' => 'mock',
        'provider_payment_id' => 'mock_bt_'.fake()->uuid(),
        'amount' => 5000,
        'status' => PaymentStatus::Pending,
    ]);

    return $order;
}

it('confirms bank transfer payment and marks order as paid', function () {
    $order = createBankTransferOrder($this->store);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->financial_status)->toBe(FinancialStatus::Paid);

    $payment = $order->payments->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);
});

it('cannot confirm non-bank-transfer orders', function () {
    $order = createBankTransferOrder($this->store);
    $order->update(['payment_method' => PaymentMethod::CreditCard]);

    expect(fn () => $this->orderService->confirmBankTransferPayment($order))
        ->toThrow(InvalidArgumentException::class, 'not a bank transfer');
});

it('cannot confirm already confirmed orders', function () {
    $order = createBankTransferOrder($this->store);
    $order->update(['financial_status' => FinancialStatus::Paid]);

    expect(fn () => $this->orderService->confirmBankTransferPayment($order))
        ->toThrow(InvalidArgumentException::class, 'not pending');
});

it('auto-cancel job cancels orders after configured days', function () {
    config(['shop.bank_transfer_expiry_days' => 7]);

    $oldOrder = createBankTransferOrder($this->store, [
        'placed_at' => now()->subDays(10),
    ]);

    $job = new CancelUnpaidBankTransferOrders;
    $job->handle($this->orderService);

    $oldOrder->refresh();
    expect($oldOrder->status)->toBe(OrderStatus::Cancelled);
});

it('auto-cancel job skips recent orders', function () {
    config(['shop.bank_transfer_expiry_days' => 7]);

    $recentOrder = createBankTransferOrder($this->store, [
        'placed_at' => now()->subDays(2),
    ]);

    $job = new CancelUnpaidBankTransferOrders;
    $job->handle($this->orderService);

    $recentOrder->refresh();
    expect($recentOrder->status)->toBe(OrderStatus::Pending);
});

it('auto-fulfills digital products on bank transfer confirmation', function () {
    $order = createBankTransferOrder($this->store, [
        'requires_shipping' => false,
    ]);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->status)->toBe(OrderStatus::Fulfilled);
});
