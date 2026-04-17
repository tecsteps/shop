<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\RefundService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeRefundFixture(int $total = 5000): array
{
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => $total,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 10,
    ]);

    $order = Order::factory()->paid()->create([
        'store_id' => $store->getKey(),
        'subtotal_amount' => $total,
        'total_amount' => $total,
    ]);

    $line = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity' => 1,
        'unit_price_amount' => $total,
        'total_amount' => $total,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->getKey(),
        'amount' => $total,
    ]);

    return [$store, $order, $payment, $line, $variant];
}

it('issues a partial refund and transitions to partially_refunded', function () {
    [, $order, $payment] = makeRefundFixture(5000);

    $refund = app(RefundService::class)->create($order, $payment, 2000, reason: 'customer request');

    expect($refund->amount)->toBe(2000)
        ->and($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('issues a full refund and transitions to refunded', function () {
    [, $order, $payment] = makeRefundFixture(3000);

    app(RefundService::class)->create($order, $payment, 3000);

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->refresh()->status)->toBe(OrderStatus::Refunded);
});

it('restocks inventory when restock flag is true', function () {
    [, $order, $payment, , $variant] = makeRefundFixture(1000);
    $startingOnHand = (int) $variant->inventoryItem()->first()->quantity_on_hand;

    app(RefundService::class)->create($order, $payment, 1000, restock: true);

    expect((int) $variant->inventoryItem()->first()->quantity_on_hand)->toBe($startingOnHand + 1);
});

it('rejects refunds that exceed remaining refundable amount', function () {
    [, $order, $payment] = makeRefundFixture(1000);

    app(RefundService::class)->create($order, $payment, 1500);
})->throws(RuntimeException::class);

it('rejects zero or negative refund amounts', function () {
    [, $order, $payment] = makeRefundFixture(1000);

    app(RefundService::class)->create($order, $payment, 0);
})->throws(RuntimeException::class);
