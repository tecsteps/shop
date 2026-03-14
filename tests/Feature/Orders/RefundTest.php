<?php

use App\Enums\FinancialStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\RefundStatus;
use App\Enums\VariantStatus;
use App\Events\OrderRefunded;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->refundService = app(RefundService::class);
});

function createPaidOrderForRefund($store, int $totalAmount = 10000): array
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $totalAmount,
        'status' => VariantStatus::Active,
    ]);
    $inventoryItem = InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => (string) fake()->unique()->numberBetween(2000, 99999),
        'email' => 'refund@example.com',
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'payment_method' => PaymentMethod::CreditCard,
        'total_amount' => $totalAmount,
        'subtotal_amount' => $totalAmount,
        'placed_at' => now(),
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => $totalAmount,
        'subtotal_amount' => $totalAmount,
        'total_amount' => $totalAmount,
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'method' => PaymentMethod::CreditCard,
        'provider' => 'mock',
        'provider_payment_id' => 'mock_pay_test',
        'amount' => $totalAmount,
        'status' => PaymentStatus::Captured,
        'captured_at' => now(),
    ]);

    return compact('order', 'payment', 'variant', 'inventoryItem');
}

it('creates a full refund and sets financial_status to refunded', function () {
    $data = createPaidOrderForRefund($this->store, 10000);

    $refund = $this->refundService->create(
        $data['order'],
        $data['payment'],
        10000,
        'Customer changed mind',
        false,
    );

    expect($refund->status)->toBe(RefundStatus::Processed);
    expect($refund->amount)->toBe(10000);
    expect($refund->reason)->toBe('Customer changed mind');

    $data['order']->refresh();
    expect($data['order']->financial_status)->toBe(FinancialStatus::Refunded);
    expect($data['order']->status)->toBe(OrderStatus::Refunded);
});

it('creates a partial refund and sets financial_status to partially_refunded', function () {
    $data = createPaidOrderForRefund($this->store, 10000);

    $refund = $this->refundService->create(
        $data['order'],
        $data['payment'],
        3000,
        'Partial refund',
        false,
    );

    expect($refund->amount)->toBe(3000);

    $data['order']->refresh();
    expect($data['order']->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('rejects refund exceeding payment amount', function () {
    $data = createPaidOrderForRefund($this->store, 10000);

    expect(fn () => $this->refundService->create(
        $data['order'],
        $data['payment'],
        15000,
        'Too much',
        false,
    ))->toThrow(InvalidArgumentException::class);
});

it('restocks inventory when restock is true', function () {
    $data = createPaidOrderForRefund($this->store, 5000);
    $initialStock = $data['inventoryItem']->quantity_on_hand;

    $this->refundService->create(
        $data['order'],
        $data['payment'],
        5000,
        'Restock requested',
        true,
    );

    $data['inventoryItem']->refresh();
    expect($data['inventoryItem']->quantity_on_hand)->toBe($initialStock + 1);
});

it('does not restock inventory when restock is false', function () {
    $data = createPaidOrderForRefund($this->store, 5000);
    $initialStock = $data['inventoryItem']->quantity_on_hand;

    $this->refundService->create(
        $data['order'],
        $data['payment'],
        5000,
        'No restock',
        false,
    );

    $data['inventoryItem']->refresh();
    expect($data['inventoryItem']->quantity_on_hand)->toBe($initialStock);
});

it('dispatches OrderRefunded event', function () {
    Event::fake([OrderRefunded::class]);

    $data = createPaidOrderForRefund($this->store, 5000);

    $this->refundService->create(
        $data['order'],
        $data['payment'],
        5000,
        'Refund test',
        false,
    );

    Event::assertDispatched(OrderRefunded::class);
});

it('records refund reason', function () {
    $data = createPaidOrderForRefund($this->store, 5000);

    $refund = $this->refundService->create(
        $data['order'],
        $data['payment'],
        2000,
        'Product arrived damaged',
        false,
    );

    expect($refund->reason)->toBe('Product arrived damaged');
});
