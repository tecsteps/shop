<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\InvalidRefundOperationException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

/**
 * @return array{0: Order, 1: OrderLine, 2: ProductVariant}
 */
function refundServiceOrder(int $quantity = 2, int $unitPrice = 5000): array
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()
        ->withDefaultVariant($unitPrice)
        ->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 8,
            'quantity_reserved' => 0,
        ]);

    $total = $quantity * $unitPrice;
    $order = Order::factory()->paid()->create([
        'store_id' => $store->getKey(),
        'subtotal_amount' => $total,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => $total,
    ]);
    $line = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity' => $quantity,
        'unit_price_amount' => $unitPrice,
        'total_amount' => $total,
    ]);

    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'status' => PaymentStatus::Captured,
        'amount' => $total,
    ]);

    return [$order, $line, $variant];
}

test('refund service processes a partial line refund and restocks inventory', function () {
    [$order, $line, $variant] = refundServiceOrder();

    Event::fake();

    $refund = app(RefundService::class)->process($order, [
        'lines' => [$line->getKey() => 1],
        'reason' => 'Customer return',
        'restock' => true,
    ]);

    expect($refund->amount)->toBe(5000)
        ->and($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->provider_refund_id)->toStartWith('mock_refund_')
        ->and($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($order->payments()->first()?->status)->toBe(PaymentStatus::Captured)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_on_hand)->toBe(9);

    Event::assertDispatched(OrderRefunded::class, fn (OrderRefunded $event): bool => $event->refund->is($refund));
});

test('refund service marks order and payment refunded when the full amount is refunded', function () {
    [$order] = refundServiceOrder();

    $refund = app(RefundService::class)->process($order);

    expect($refund->amount)->toBe(10000)
        ->and($order->refresh()->status)->toBe(OrderStatus::Refunded)
        ->and($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->payments()->first()?->status)->toBe(PaymentStatus::Refunded);
});

test('refund service rejects over-refunds and orders without captured payments', function () {
    [$order] = refundServiceOrder();

    expect(fn () => app(RefundService::class)->process($order, ['amount' => 10001]))
        ->toThrow(InvalidRefundOperationException::class);

    $order->payments()->update(['status' => PaymentStatus::Pending]);

    expect(fn () => app(RefundService::class)->process($order, ['amount' => 500]))
        ->toThrow(InvalidRefundOperationException::class);
});
