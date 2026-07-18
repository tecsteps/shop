<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('processes full refunds and optionally restocks lines', function () {
    $order = Order::factory()->create(['total_amount' => 1000]);
    $product = Product::factory()->create(['store_id' => $order->store_id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $inventory = InventoryItem::factory()->create([
        'store_id' => $order->store_id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 4,
    ]);
    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
    ]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 1000]);

    $refund = app(RefundService::class)->process($order, [
        'lines' => [$line->id => 1],
        'restock' => true,
        'reason' => 'Returned',
    ]);

    expect($refund->amount)->toBe(1000)
        ->and($order->refresh()->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded)
        ->and($inventory->refresh()->quantity_on_hand)->toBe(5);
});
