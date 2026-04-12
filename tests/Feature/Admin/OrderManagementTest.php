<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrdersShow;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function setupPaidOrder(int $storeId): Order
{
    $product = Product::factory()->create(['store_id' => $storeId]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
    ]);
    InventoryItem::create([
        'store_id' => $storeId,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $order = Order::factory()->paid()->create([
        'store_id' => $storeId,
        'total_amount' => 5000,
        'subtotal_amount' => 5000,
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    Payment::factory()->captured()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    return $order->fresh(['lines', 'payments']);
}

it('lists orders in the admin index', function (): void {
    [$user, $store] = loginAsAdmin();

    Order::factory()->create([
        'store_id' => $store->id,
        'order_number' => '#9001',
        'email' => 'buyer@example.com',
    ]);

    Livewire::test(OrdersIndex::class)
        ->assertSee('#9001')
        ->assertSee('buyer@example.com');
});

it('shows an order detail page', function (): void {
    [$user, $store] = loginAsAdmin();

    $order = setupPaidOrder($store->id);

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->assertSee($order->order_number);
});

it('creates a fulfillment for a paid order', function (): void {
    [$user, $store] = loginAsAdmin();

    $order = setupPaidOrder($store->id);
    $line = $order->lines->first();

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->set('fulfillLines.'.$line->id, (int) $line->quantity)
        ->call('createFulfillment');

    expect($order->fresh()->fulfillments()->count())->toBe(1);
});

it('refunds an order', function (): void {
    [$user, $store] = loginAsAdmin();

    $order = setupPaidOrder($store->id);

    Livewire::test(OrdersShow::class, ['order' => $order])
        ->set('refundAmount', 1000)
        ->set('refundReason', 'Customer request')
        ->call('createRefund');

    expect($order->fresh()->refunds()->count())->toBe(1)
        ->and($order->fresh()->refundedTotal())->toBe(1000);
});

it('confirms a bank transfer payment', function (): void {
    [$user, $store] = loginAsAdmin();

    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
    ]);
    InventoryItem::create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 1,
        'policy' => 'deny',
    ]);

    $order = Order::factory()->create([
        'store_id' => $store->id,
        'payment_method' => PaymentMethod::BankTransfer->value,
        'status' => OrderStatus::Pending->value,
        'financial_status' => FinancialStatus::Pending->value,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'total_amount' => 2500,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'method' => PaymentMethod::BankTransfer->value,
        'status' => PaymentStatus::Pending->value,
        'amount' => 2500,
    ]);

    Livewire::test(OrdersShow::class, ['order' => $order->fresh(['lines', 'payments'])])
        ->call('confirmBankTransfer');

    expect($order->fresh()->financial_status->value)->toBe('paid');
});
