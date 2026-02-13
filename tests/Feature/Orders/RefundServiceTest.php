<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\InvalidRefundException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(RefundService::class);
});

function createPaidOrderWithPayment(Store $store, int $totalAmount = 5000): array
{
    $order = Order::factory()->create([
        'store_id' => $store->id,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'total_amount' => $totalAmount,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => $totalAmount,
        'status' => PaymentStatus::Captured,
    ]);

    return [$order, $payment];
}

test('full refund sets order to refunded', function () {
    Event::fake();

    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $refund = $this->service->create($order, $payment);

    expect($refund->amount)->toBe(5000);
    expect($refund->status)->toBe(RefundStatus::Processed);
    expect($refund->provider_refund_id)->toStartWith('mock_refund_');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded);
    expect($order->status)->toBe(OrderStatus::Refunded);

    Event::assertDispatched(OrderRefunded::class);
});

test('partial refund sets order to partially_refunded', function () {
    Event::fake();

    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $refund = $this->service->create($order, $payment, amount: 2000, reason: 'Partial return');

    expect($refund->amount)->toBe(2000);
    expect($refund->reason)->toBe('Partial return');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

test('multiple partial refunds leading to full refund', function () {
    Event::fake();

    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $this->service->create($order, $payment, amount: 2000);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $this->service->create($order, $payment, amount: 3000);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Refunded);
    expect($order->status)->toBe(OrderStatus::Refunded);
});

test('refund exceeding refundable amount throws exception', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    expect(fn () => $this->service->create($order, $payment, amount: 6000))
        ->toThrow(InvalidRefundException::class);
});

test('refund with zero amount throws exception', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    expect(fn () => $this->service->create($order, $payment, amount: 0))
        ->toThrow(InvalidRefundException::class);
});

test('refund with negative amount throws exception', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    expect(fn () => $this->service->create($order, $payment, amount: -100))
        ->toThrow(InvalidRefundException::class);
});

test('refund after previous refund respects remaining amount', function () {
    [$order, $payment] = createPaidOrderWithPayment($this->store, 5000);

    $this->service->create($order, $payment, amount: 3000);

    // Remaining refundable is 2000
    expect(fn () => $this->service->create($order, $payment, amount: 2500))
        ->toThrow(InvalidRefundException::class);
});

test('refund with restock replenishes inventory for full refund', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $inventory = InventoryItem::factory()->withStock(8, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'financial_status' => FinancialStatus::Paid,
    ]);

    $orderLine = $order->lines()->create([
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Test',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    $this->service->create($order, $payment, restock: true);

    $inventory->refresh();
    expect($inventory->quantity_on_hand)->toBe(10);
});

test('refund with restock for specific lines restocks only those', function () {
    Event::fake();

    $product1 = Product::factory()->create(['store_id' => $this->store->id]);
    $variant1 = ProductVariant::factory()->create(['product_id' => $product1->id, 'price_amount' => 2000]);
    $inventory1 = InventoryItem::factory()->withStock(10, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant1->id,
    ]);

    $product2 = Product::factory()->create(['store_id' => $this->store->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product2->id, 'price_amount' => 3000]);
    $inventory2 = InventoryItem::factory()->withStock(10, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant2->id,
    ]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 7000,
        'financial_status' => FinancialStatus::Paid,
    ]);

    $line1 = $order->lines()->create([
        'product_id' => $product1->id,
        'variant_id' => $variant1->id,
        'title_snapshot' => 'Product 1',
        'quantity' => 2,
        'unit_price_amount' => 2000,
        'total_amount' => 4000,
    ]);

    $line2 = $order->lines()->create([
        'product_id' => $product2->id,
        'variant_id' => $variant2->id,
        'title_snapshot' => 'Product 2',
        'quantity' => 1,
        'unit_price_amount' => 3000,
        'total_amount' => 3000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 7000,
    ]);

    // Refund only line 1 (1 unit)
    $this->service->create(
        $order,
        $payment,
        amount: 2000,
        restock: true,
        lines: [$line1->id => 1],
    );

    $inventory1->refresh();
    expect($inventory1->quantity_on_hand)->toBe(11);

    $inventory2->refresh();
    expect($inventory2->quantity_on_hand)->toBe(10);
});

test('refund without restock does not change inventory', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $inventory = InventoryItem::factory()->withStock(8, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'financial_status' => FinancialStatus::Paid,
    ]);

    $order->lines()->create([
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Test',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    $this->service->create($order, $payment, restock: false);

    $inventory->refresh();
    expect($inventory->quantity_on_hand)->toBe(8);
});
