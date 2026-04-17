<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->refundService = app(RefundService::class);

    $this->product = Product::factory()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
    ]);
    $this->inventory = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 8,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $this->order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'total_amount' => 5000,
    ]);
    $this->orderLine = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);
    $this->payment = Payment::factory()->create([
        'order_id' => $this->order->id,
        'amount' => 5000,
        'status' => PaymentStatus::Captured,
    ]);
});

it('creates a full refund successfully', function () {
    Event::fake();

    $refund = $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 5000,
        reason: 'Customer unhappy',
    );

    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->amount)->toBe(5000)
        ->and($refund->reason)->toBe('Customer unhappy')
        ->and($refund->provider_refund_id)->toStartWith('mock_refund_');

    $this->order->refresh();
    expect($this->order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($this->order->status)->toBe(OrderStatus::Refunded);

    Event::assertDispatched(OrderRefunded::class);
});

it('creates a partial refund and updates to partially_refunded', function () {
    $refund = $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 2000,
    );

    expect($refund->amount)->toBe(2000);

    $this->order->refresh();
    expect($this->order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('prevents refund exceeding remaining refundable amount', function () {
    // Create a prior refund of 3000
    \App\Models\Refund::create([
        'order_id' => $this->order->id,
        'payment_id' => $this->payment->id,
        'amount' => 3000,
        'status' => RefundStatus::Processed,
        'created_at' => now()->toIso8601String(),
    ]);

    expect(fn () => $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 3000,
    ))->toThrow(RuntimeException::class, 'exceeds remaining refundable amount');
});

it('prevents zero amount refunds', function () {
    expect(fn () => $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 0,
    ))->toThrow(RuntimeException::class, 'greater than zero');
});

it('restocks inventory when restock flag is true', function () {
    $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 5000,
        restock: true,
    );

    $this->inventory->refresh();
    // quantity was 8, restock qty 2 -> 10
    expect($this->inventory->quantity_on_hand)->toBe(10);
});

it('does not restock inventory when restock flag is false', function () {
    $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 5000,
        restock: false,
    );

    $this->inventory->refresh();
    expect($this->inventory->quantity_on_hand)->toBe(8);
});

it('transitions to fully refunded after multiple partial refunds', function () {
    $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 2000,
    );

    $this->order->refresh();
    expect($this->order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $this->refundService->create(
        order: $this->order,
        payment: $this->payment,
        amount: 3000,
    );

    $this->order->refresh();
    expect($this->order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($this->order->status)->toBe(OrderStatus::Refunded);
});
