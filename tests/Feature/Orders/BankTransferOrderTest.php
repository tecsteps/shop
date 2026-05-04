<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPaid;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

/**
 * @return array{0: Order, 1: ProductVariant}
 */
function bankTransferOrder(bool $digital = false, int $ageDays = 1): array
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    StoreSettings::query()->create([
        'store_id' => $store->getKey(),
        'settings_json' => ['bank_transfer_cancel_days' => 7],
    ]);

    $product = Product::factory()->withDefaultVariant(2500)->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()->where('product_id', $product->getKey())->firstOrFail();
    $variant->forceFill([
        'requires_shipping' => ! $digital,
        'weight_g' => $digital ? 0 : $variant->weight_g,
    ])->save();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 10,
            'quantity_reserved' => 2,
        ]);

    $order = Order::factory()->bankTransfer()->create([
        'store_id' => $store->getKey(),
        'payment_method' => PaymentMethod::BankTransfer,
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'placed_at' => now()->subDays($ageDays),
        'subtotal_amount' => 5000,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 5000,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);
    Payment::factory()->bankTransfer()->create([
        'order_id' => $order->getKey(),
        'amount' => 5000,
    ]);

    return [$order, $variant];
}

test('confirming bank transfer payment captures payment and commits reserved inventory', function () {
    [$order, $variant] = bankTransferOrder();

    Event::fake();

    $paidOrder = app(OrderService::class)->confirmBankTransferPayment($order);
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($paidOrder->status)->toBe(OrderStatus::Paid)
        ->and($paidOrder->financial_status)->toBe(FinancialStatus::Paid)
        ->and($paidOrder->payments->first()->status)->toBe(PaymentStatus::Captured)
        ->and($inventory->quantity_on_hand)->toBe(8)
        ->and($inventory->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderPaid::class);
});

test('confirming digital bank transfer payment auto-fulfills the order', function () {
    [$order] = bankTransferOrder(digital: true);

    $paidOrder = app(OrderService::class)->confirmBankTransferPayment($order);

    expect($paidOrder->status)->toBe(OrderStatus::Fulfilled)
        ->and($paidOrder->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($paidOrder->fulfillments)->toHaveCount(1)
        ->and($paidOrder->fulfillments->first()->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

test('cancel job voids stale bank transfer orders and releases reservations', function () {
    [$oldOrder, $oldVariant] = bankTransferOrder(ageDays: 8);
    [$newOrder, $newVariant] = bankTransferOrder(ageDays: 2);

    Event::fake();

    (new CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));

    $oldInventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $oldVariant->getKey())->firstOrFail();
    $newInventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $newVariant->getKey())->firstOrFail();

    expect($oldOrder->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($oldOrder->financial_status)->toBe(FinancialStatus::Voided)
        ->and($oldOrder->payments()->first()?->status)->toBe(PaymentStatus::Failed)
        ->and($oldInventory->quantity_on_hand)->toBe(10)
        ->and($oldInventory->quantity_reserved)->toBe(0)
        ->and($newOrder->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($newInventory->quantity_reserved)->toBe(2);

    Event::assertDispatched(OrderCancelled::class);
});
