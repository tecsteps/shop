<?php

use App\Enums\FinancialStatus;
use App\Enums\RefundStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;
use App\Services\Payments\MockPaymentProvider;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);

    $this->refundService = new RefundService(new MockPaymentProvider, new InventoryService);

    $product = Product::factory()->for($this->store)->create(['title' => 'Candle']);
    $this->variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 1500,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()
        ->for($this->store)
        ->for($this->variant, 'variant')
        ->create(['quantity_on_hand' => 8]);

    $this->order = Order::factory()->for($this->store)->paid()->create([
        'subtotal_amount' => 3000,
        'total_amount' => 3000,
    ]);
    OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'variant_id' => $this->variant->id,
        'title_snapshot' => 'Candle',
        'quantity' => 2,
        'unit_price_amount' => 1500,
        'total_amount' => 3000,
    ]);
    $this->payment = Payment::factory()->captured()->create([
        'order_id' => $this->order->id,
        'amount' => 3000,
    ]);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a partial refund and marks order as partially refunded', function (): void {
    $refund = $this->refundService->create($this->order, $this->payment, 1000, 'customer_request', false);

    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->amount)->toBe(1000)
        ->and($this->order->fresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('creates a full refund and marks order as refunded', function (): void {
    $this->refundService->create($this->order, $this->payment, 3000, 'customer_request', false);

    expect($this->order->fresh()->financial_status)->toBe(FinancialStatus::Refunded);
});

it('restocks inventory when restock flag is true', function (): void {
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $this->variant->id)->first();
    $startingStock = $inventory->quantity_on_hand;

    $this->refundService->create($this->order, $this->payment, 3000, 'customer_request', true);

    expect($inventory->fresh()->quantity_on_hand)->toBe($startingStock + 2);
});

it('does not restock inventory when restock flag is false', function (): void {
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $this->variant->id)->first();
    $startingStock = $inventory->quantity_on_hand;

    $this->refundService->create($this->order, $this->payment, 1500, null, false);

    expect($inventory->fresh()->quantity_on_hand)->toBe($startingStock);
});

it('rejects a refund amount exceeding the remaining payment balance', function (): void {
    $this->refundService->create($this->order, $this->payment, 3000, null, false);

    expect(fn () => $this->refundService->create($this->order->fresh(), $this->payment->fresh(), 1, null, false))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects refunds with zero or negative amount', function (): void {
    expect(fn () => $this->refundService->create($this->order, $this->payment, 0, null, false))
        ->toThrow(InvalidArgumentException::class);
});
