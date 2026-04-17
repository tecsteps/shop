<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Exceptions\RefundExceedsPaymentException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RefundService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->refundService = app(RefundService::class);
});

it('creates a full refund', function () {
    $order = createPaidOrderForRefund($this->store, 5000);

    $refund = $this->refundService->create($order, 5000);

    expect($refund->amount)->toBe(5000)
        ->and($refund->status)->toBe(RefundStatus::Processed);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded);
});

it('creates a partial refund', function () {
    $order = createPaidOrderForRefund($this->store, 5000);

    $refund = $this->refundService->create($order, 2000);

    expect($refund->amount)->toBe(2000);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('rejects refund exceeding payment amount', function () {
    $order = createPaidOrderForRefund($this->store, 5000);

    $this->refundService->create($order, 6000);
})->throws(RefundExceedsPaymentException::class);

it('restocks inventory when restock flag is true', function () {
    $order = createPaidOrderForRefund($this->store, 5000);
    $line = $order->lines->first();
    $item = InventoryItem::query()
        ->withoutGlobalScopes()
        ->where('variant_id', $line->variant_id)
        ->first();
    $originalOnHand = $item->quantity_on_hand;

    $this->refundService->create($order, 5000, 'Customer requested', restock: true);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe($originalOnHand + $line->quantity);
});

it('does not restock when restock flag is false', function () {
    $order = createPaidOrderForRefund($this->store, 5000);
    $line = $order->lines->first();
    $item = InventoryItem::query()
        ->withoutGlobalScopes()
        ->where('variant_id', $line->variant_id)
        ->first();
    $originalOnHand = $item->quantity_on_hand;

    $this->refundService->create($order, 5000, restock: false);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe($originalOnHand);
});

it('records refund reason', function () {
    $order = createPaidOrderForRefund($this->store, 5000);

    $refund = $this->refundService->create($order, 5000, 'Customer requested');

    expect($refund->reason)->toBe('Customer requested');
});

it('only allows refund of captured payment', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'financial_status' => FinancialStatus::Pending,
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id, 'amount' => 5000]);

    $this->refundService->create($order, 5000);
})->throws(\RuntimeException::class, 'No captured payment');

function createPaidOrderForRefund(\App\Models\Store $store, int $totalAmount): Order
{
    /** @var Order $order */
    $order = Order::factory()->paid()->create([
        'store_id' => $store->id,
        'total_amount' => $totalAmount,
    ]);

    Payment::factory()->captured()->create([
        'order_id' => $order->id,
        'amount' => $totalAmount,
    ]);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 8,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    return $order;
}
