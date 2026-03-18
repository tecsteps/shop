<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

function createRefundTestContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $variant = ProductVariant::create([
        'product_id' => \App\Models\Product::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'title' => 'Refund Product',
            'handle' => 'refund-product-'.rand(1000, 9999),
            'status' => 'active',
            'published_at' => now(),
        ])->id,
        'sku' => 'REF-001',
        'price_amount' => 3000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
        'requires_shipping' => true,
    ]);

    $inventory = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 48,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#1001',
        'payment_method' => PaymentMethod::CreditCard,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'EUR',
        'subtotal_amount' => 6000,
        'total_amount' => 6000,
        'placed_at' => now(),
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Refund Product',
        'sku_snapshot' => 'REF-001',
        'price_amount' => 3000,
        'quantity' => 2,
        'total_amount' => 6000,
        'requires_shipping' => true,
    ]);

    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'mock',
        'method' => PaymentMethod::CreditCard,
        'provider_payment_id' => 'mock_test123',
        'status' => PaymentStatus::Captured,
        'amount' => 6000,
        'currency' => 'EUR',
        'created_at' => now(),
    ]);

    return array_merge($ctx, compact('order', 'payment', 'variant', 'inventory'));
}

it('processes a partial refund', function () {
    $ctx = createRefundTestContext();
    Event::fake([OrderRefunded::class]);
    $refundService = app(RefundService::class);

    $refund = $refundService->create($ctx['order'], $ctx['payment'], 2000, 'Partial refund');

    expect($refund->amount)->toBe(2000)
        ->and($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->provider_refund_id)->toStartWith('mock_refund_')
        ->and($ctx['order']->fresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    Event::assertDispatched(OrderRefunded::class);
});

it('processes a full refund', function () {
    $ctx = createRefundTestContext();
    $refundService = app(RefundService::class);

    $refund = $refundService->create($ctx['order'], $ctx['payment'], 6000, 'Full refund');

    expect($refund->amount)->toBe(6000)
        ->and($ctx['order']->fresh()->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($ctx['order']->fresh()->status)->toBe(OrderStatus::Refunded);
});

it('rejects refund exceeding refundable amount', function () {
    $ctx = createRefundTestContext();
    $refundService = app(RefundService::class);

    expect(fn () => $refundService->create($ctx['order'], $ctx['payment'], 7000))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects zero amount refund', function () {
    $ctx = createRefundTestContext();
    $refundService = app(RefundService::class);

    expect(fn () => $refundService->create($ctx['order'], $ctx['payment'], 0))
        ->toThrow(InvalidArgumentException::class);
});

it('restocks inventory when restock flag is true', function () {
    $ctx = createRefundTestContext();
    $refundService = app(RefundService::class);

    $refundService->create($ctx['order'], $ctx['payment'], 6000, 'Restock refund', restock: true);

    $item = $ctx['inventory']->fresh();
    expect($item->quantity_on_hand)->toBe(50);
});

it('does not restock inventory when restock flag is false', function () {
    $ctx = createRefundTestContext();
    $refundService = app(RefundService::class);

    $refundService->create($ctx['order'], $ctx['payment'], 6000, 'No restock', restock: false);

    $item = $ctx['inventory']->fresh();
    expect($item->quantity_on_hand)->toBe(48);
});

it('handles multiple partial refunds correctly', function () {
    $ctx = createRefundTestContext();
    $refundService = app(RefundService::class);

    $refundService->create($ctx['order'], $ctx['payment'], 2000, 'First partial');
    expect($ctx['order']->fresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $refundService->create($ctx['order']->fresh(), $ctx['payment'], 4000, 'Second partial');
    expect($ctx['order']->fresh()->financial_status)->toBe(FinancialStatus::Refunded);
});
