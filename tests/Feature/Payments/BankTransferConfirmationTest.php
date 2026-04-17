<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BankTransferService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(BankTransferService::class);
});

it('admin can confirm bank transfer payment', function () {
    $order = createBankTransferOrder($this->store);

    $result = $this->service->confirmPayment($order);

    expect($result->financial_status)->toBe(FinancialStatus::Paid)
        ->and($result->status)->toBe(OrderStatus::Paid);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);
});

it('cannot confirm payment for non-bank-transfer orders', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
    ]);
    Payment::factory()->captured()->create(['order_id' => $order->id]);

    $this->service->confirmPayment($order);
})->throws(\InvalidArgumentException::class, 'Only bank transfer orders');

it('cannot confirm already confirmed payment', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::BankTransfer,
        'financial_status' => FinancialStatus::Paid,
        'status' => OrderStatus::Paid,
    ]);
    Payment::factory()->captured()->create(['order_id' => $order->id]);

    $this->service->confirmPayment($order);
})->throws(\InvalidArgumentException::class, 'already been confirmed');

it('auto-cancel job cancels unpaid bank transfer orders after config days', function () {
    $order = createBankTransferOrder($this->store, placedDaysAgo: 8);

    (new CancelUnpaidBankTransferOrders)->handle(app(\App\Services\InventoryService::class));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->financial_status)->toBe(FinancialStatus::Voided);
});

it('auto-cancel job does not cancel orders within config days', function () {
    $order = createBankTransferOrder($this->store, placedDaysAgo: 2);

    (new CancelUnpaidBankTransferOrders)->handle(app(\App\Services\InventoryService::class));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending);
});

it('auto-fulfills digital products on payment confirmation', function () {
    $order = createBankTransferOrder($this->store, digital: true);

    $result = $this->service->confirmPayment($order);

    $result->refresh();
    expect($result->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($result->status)->toBe(OrderStatus::Fulfilled);
    expect($result->fulfillments()->count())->toBe(1);
});

function createBankTransferOrder(
    \App\Models\Store $store,
    int $placedDaysAgo = 0,
    bool $digital = false,
): Order {
    /** @var Order $order */
    $order = Order::factory()->bankTransfer()->create([
        'store_id' => $store->id,
        'placed_at' => now()->subDays($placedDaysAgo),
        'total_amount' => 5000,
    ]);

    Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'method' => PaymentMethod::BankTransfer,
    ]);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => ! $digital,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 2,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    return $order;
}
