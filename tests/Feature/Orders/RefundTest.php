<?php

use App\Enums\FinancialStatus;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($product)->create();
    $this->variant->inventoryItem->update(['quantity_on_hand' => 8]);
    $this->order = Order::factory()->for($this->store)->create(['total_amount' => 5000, 'financial_status' => FinancialStatus::Paid]);
    OrderLine::factory()->for($this->order)->create(['product_id' => $product->id, 'variant_id' => $this->variant->id, 'quantity' => 2, 'total_amount' => 5000]);
    $this->payment = Payment::factory()->for($this->order)->create(['amount' => 5000]);
    $this->service = app(RefundService::class);
});

it('processes partial and full refunds', function () {
    $partial = $this->service->create($this->order, $this->payment, 2000, 'Partial');
    expect($partial->status->value)->toBe('processed')
        ->and($this->order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $this->service->create($this->order, $this->payment, 3000, 'Remaining');
    expect($this->order->refresh()->financial_status)->toBe(FinancialStatus::Refunded);
});

it('rejects over refunds and can restock', function () {
    expect(fn () => $this->service->create($this->order, $this->payment, 5001))->toThrow(ValidationException::class);

    $this->service->create($this->order, $this->payment, 5000, restock: true);
    expect($this->variant->inventoryItem->refresh()->quantity_on_hand)->toBe(10);
});
