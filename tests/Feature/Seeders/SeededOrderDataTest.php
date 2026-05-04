<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

function seededOrderStore(string $handle): Store
{
    return Store::query()->where('handle', $handle)->firstOrFail();
}

function seededOrder(Store $store, string $orderNumber): Order
{
    return Order::withoutGlobalScopes()
        ->with(['lines.product', 'payments', 'fulfillments.lines.orderLine.product', 'refunds'])
        ->where('store_id', $store->getKey())
        ->where('order_number', $orderNumber)
        ->firstOrFail();
}

test('database seeder creates deterministic customer and order scenarios', function (): void {
    $fashion = seededOrderStore('acme-fashion');
    $electronics = seededOrderStore('acme-electronics');

    expect(Customer::withoutGlobalScopes()->where('store_id', $fashion->getKey())->count())->toBe(10)
        ->and(Customer::withoutGlobalScopes()->where('store_id', $electronics->getKey())->count())->toBe(2)
        ->and(Order::withoutGlobalScopes()->where('store_id', $fashion->getKey())->count())->toBe(15)
        ->and(Order::withoutGlobalScopes()->where('store_id', $electronics->getKey())->count())->toBe(3);

    $john = Customer::withoutGlobalScopes()
        ->where('store_id', $fashion->getKey())
        ->where('email', 'customer@acme.test')
        ->firstOrFail();
    $jane = Customer::withoutGlobalScopes()
        ->where('store_id', $fashion->getKey())
        ->where('email', 'jane@example.com')
        ->firstOrFail();

    expect($john->addresses()->count())->toBe(2)
        ->and($jane->addresses()->count())->toBe(1);

    $awaitingFulfillment = seededOrder($fashion, '#1001');

    expect($awaitingFulfillment->status)->toBe(OrderStatus::Paid)
        ->and($awaitingFulfillment->financial_status)->toBe(FinancialStatus::Paid)
        ->and($awaitingFulfillment->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($awaitingFulfillment->total_amount)->toBe(5497)
        ->and($awaitingFulfillment->lines)->toHaveCount(1)
        ->and($awaitingFulfillment->lines->first()->title_snapshot)->toContain('Classic Cotton T-Shirt')
        ->and($awaitingFulfillment->lines->first()->quantity)->toBe(2)
        ->and($awaitingFulfillment->payments->first()->status)->toBe(PaymentStatus::Captured);

    $delivered = seededOrder($fashion, '#1002');

    expect($delivered->fulfillments)->toHaveCount(1)
        ->and($delivered->fulfillments->first()->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($delivered->fulfillments->first()->lines)->toHaveCount(2);

    $refunded = seededOrder($fashion, '#1004');

    expect($refunded->status)->toBe(OrderStatus::Cancelled)
        ->and($refunded->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($refunded->payments->first()->status)->toBe(PaymentStatus::Refunded)
        ->and($refunded->refunds->first()->status)->toBe(RefundStatus::Processed)
        ->and($refunded->refunds->first()->amount)->toBe(2998);

    $discounted = seededOrder($fashion, '#1015');
    $allocations = $discounted->lines->pluck('discount_allocations_json')->flatten(1);

    expect($discounted->discount_amount)->toBe(550)
        ->and($allocations)->toHaveCount(2)
        ->and($allocations->pluck('code')->all())->toBe(['WELCOME10', 'WELCOME10'])
        ->and($allocations->sum('amount'))->toBe(550);

    $electronicsOrder = seededOrder($electronics, '#5001');

    expect($electronicsOrder->total_amount)->toBe(121298)
        ->and($electronicsOrder->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($electronicsOrder->lines)->toHaveCount(2);

    $bankTransfer = seededOrder($fashion, '#1005');
    $bankTransferLine = $bankTransfer->lines->firstOrFail();
    $inventory = InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $bankTransferLine->variant_id)
        ->firstOrFail();

    expect($bankTransfer->financial_status)->toBe(FinancialStatus::Pending)
        ->and($bankTransfer->payments->first()->status)->toBe(PaymentStatus::Pending)
        ->and($inventory->quantity_reserved)->toBe($bankTransferLine->quantity);
});
