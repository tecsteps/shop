<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->refundService = app(RefundService::class);
});

it('creates a full refund', function () {
    Event::fake();

    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'status' => PaymentStatus::Captured,
    ]);

    $refund = $this->refundService->create($order, $payment, 5000, 'Customer request');

    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->amount)->toBe(5000)
        ->and($refund->reason)->toBe('Customer request')
        ->and($refund->provider_refund_id)->toStartWith('mock_refund_');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded);

    Event::assertDispatched(OrderRefunded::class);
});

it('creates a partial refund', function () {
    Event::fake();

    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
        'status' => PaymentStatus::Captured,
    ]);

    $refund = $this->refundService->create($order, $payment, 2000);

    expect($refund->amount)->toBe(2000);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    Event::assertDispatched(OrderRefunded::class);
});

it('rejects refund exceeding total amount', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    expect(fn () => $this->refundService->create($order, $payment, 6000))
        ->toThrow(RuntimeException::class);
});

it('rejects refund exceeding remaining refundable amount', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    // First refund of 3000
    Event::fake();
    $this->refundService->create($order, $payment, 3000);

    // Second refund of 3000 should fail (only 2000 remaining)
    expect(fn () => $this->refundService->create($order->fresh(), $payment, 3000))
        ->toThrow(RuntimeException::class);
});

it('restocks inventory when restock flag is true', function () {
    Event::fake();

    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);

    $product = \App\Models\Product::withoutEvents(fn () => \App\Models\Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => \App\Enums\ProductStatus::Active,
    ]));

    $variant = \App\Models\ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'is_default' => true,
    ]);

    $inventory = \App\Models\InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
    ]);

    $order->lines()->create([
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    $this->refundService->create($order, $payment, 5000, 'Restock test', true);

    expect($inventory->fresh()->quantity_on_hand)->toBe(7);
});
