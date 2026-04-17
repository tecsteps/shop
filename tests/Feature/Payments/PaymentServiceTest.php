<?php

use App\Contracts\PaymentProvider;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Payment\MockPaymentProvider;
use App\Services\PaymentService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->paymentService = app(PaymentService::class);
});

it('resolves PaymentProvider from the container', function () {
    $provider = app(PaymentProvider::class);

    expect($provider)->toBeInstanceOf(MockPaymentProvider::class);
});

it('does not auto-fulfill when order has physical products', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 1,
        'policy' => 'deny',
    ]);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id]);

    $this->paymentService->confirmBankTransfer($order);

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->fulfillments)->toHaveCount(0);
});

it('does not auto-fulfill mixed physical and digital order', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $physicalVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => true,
    ]);
    $digitalVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $physicalVariant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 1,
        'policy' => 'deny',
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $digitalVariant->id,
        'quantity_on_hand' => 100,
        'quantity_reserved' => 1,
        'policy' => 'deny',
    ]);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'variant_id' => $physicalVariant->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'variant_id' => $digitalVariant->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id]);

    $this->paymentService->confirmBankTransfer($order);

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->fulfillments)->toHaveCount(0);
});

it('auto-fulfills all-digital order on bank transfer confirmation', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $digitalVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $digitalVariant->id,
        'quantity_on_hand' => 100,
        'quantity_reserved' => 2,
        'policy' => 'deny',
    ]);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'variant_id' => $digitalVariant->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id]);

    $this->paymentService->confirmBankTransfer($order);

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled)
        ->and($order->fulfillments)->toHaveCount(1)
        ->and($order->fulfillments->first()->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($order->fulfillments->first()->fulfillmentLines)->toHaveCount(1);
});

it('does not cancel orders within the timeout period', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'placed_at' => now()->subDays(3)->toIso8601String(),
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'variant_id' => $variant->id,
        'product_id' => $product->id,
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id]);

    $job = new \App\Jobs\CancelUnpaidBankTransferOrders;
    $job->handle(app(\App\Services\InventoryService::class));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Pending);
});
