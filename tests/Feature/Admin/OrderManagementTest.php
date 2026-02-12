<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;

it('supports bank transfer confirmation plus fulfillment and refund actions', function () {
    $store = Store::factory()->create();
    $user = createStoreAdmin($store);

    $product = Product::factory()->for($store)->create();

    $variant = ProductVariant::factory()
        ->for($product)
        ->default()
        ->state([
            'requires_shipping' => true,
        ])
        ->create();

    $inventory = InventoryItem::factory()
        ->for($store)
        ->for($variant, 'variant')
        ->state([
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ])
        ->create();

    $order = Order::factory()
        ->for($store)
        ->create([
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000,
        ]);

    $line = OrderLine::factory()
        ->for($order)
        ->for($product)
        ->for($variant, 'variant')
        ->create([
            'title_snapshot' => $product->title,
            'sku_snapshot' => $variant->sku,
            'quantity' => 2,
            'unit_price_amount' => 2500,
            'total_amount' => 5000,
        ]);

    Payment::factory()
        ->for($order)
        ->create([
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'amount' => 5000,
            'currency' => $order->currency,
        ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->post("/admin/orders/{$order->id}/confirm-bank-transfer")
        ->assertRedirect();

    $order->refresh();
    $payment = $order->payments()->latest('id')->firstOrFail();

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($payment->status)->toBe(PaymentStatus::Captured);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->post("/admin/orders/{$order->id}/fulfillments", [
            'lines' => [$line->id => 2],
            'tracking_company' => 'DHL',
            'tracking_number' => 'TRACK-123',
            'tracking_url' => 'https://tracking.example.test/TRACK-123',
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    $this->assertDatabaseHas('fulfillments', [
        'order_id' => $order->id,
        'tracking_number' => 'TRACK-123',
    ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->post("/admin/orders/{$order->id}/refunds", [
            'amount' => 1000,
            'reason' => 'Customer changed mind',
            'restock' => true,
            'lines' => [$line->id => 1],
        ])
        ->assertRedirect();

    $order->refresh();
    $inventory->refresh();

    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($inventory->quantity_on_hand)->toBe(11);

    $this->assertDatabaseHas('refunds', [
        'order_id' => $order->id,
        'amount' => 1000,
        'status' => RefundStatus::Processed->value,
    ]);
});
