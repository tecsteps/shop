<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->fulfillmentService = app(FulfillmentService::class);
});

function createPaidOrderForFulfillment($store, array $lineItems = []): Order
{
    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => (string) fake()->unique()->numberBetween(3000, 99999),
        'email' => 'fulfill@example.com',
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_method' => PaymentMethod::CreditCard,
        'total_amount' => 10000,
        'subtotal_amount' => 10000,
        'placed_at' => now(),
    ]);

    if (empty($lineItems)) {
        $lineItems = [['quantity' => 2, 'price' => 2500]];
    }

    foreach ($lineItems as $item) {
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::Active,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $item['price'],
            'status' => VariantStatus::Active,
            'requires_shipping' => $item['requires_shipping'] ?? true,
        ]);
        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => 20,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ]);

        OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'title_snapshot' => $product->title,
            'sku_snapshot' => $variant->sku,
            'quantity' => $item['quantity'],
            'unit_price_amount' => $item['price'],
            'subtotal_amount' => $item['price'] * $item['quantity'],
            'total_amount' => $item['price'] * $item['quantity'],
            'requires_shipping' => $item['requires_shipping'] ?? true,
        ]);
    }

    Payment::create([
        'order_id' => $order->id,
        'method' => PaymentMethod::CreditCard,
        'provider' => 'mock',
        'provider_payment_id' => 'mock_pay_fulfill',
        'amount' => 10000,
        'status' => PaymentStatus::Captured,
        'captured_at' => now(),
    ]);

    return $order;
}

it('creates fulfillment for specific lines', function () {
    $order = createPaidOrderForFulfillment($this->store);
    $orderLine = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);

    expect($fulfillment)->not->toBeNull();
    expect($fulfillment->order_id)->toBe($order->id);
    expect($fulfillment->lines)->toHaveCount(1);
    expect($fulfillment->lines->first()->quantity)->toBe($orderLine->quantity);
});

it('updates fulfillment_status to partial when not all lines fulfilled', function () {
    $order = createPaidOrderForFulfillment($this->store, [
        ['quantity' => 2, 'price' => 2500],
        ['quantity' => 3, 'price' => 3000],
    ]);
    $firstLine = $order->lines->first();

    $this->fulfillmentService->create($order, [
        $firstLine->id => $firstLine->quantity,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('updates to fulfilled when all lines are fulfilled', function () {
    $order = createPaidOrderForFulfillment($this->store);
    $orderLine = $order->lines->first();

    $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->status)->toBe(OrderStatus::Fulfilled);
});

it('adds tracking info to fulfillment', function () {
    $order = createPaidOrderForFulfillment($this->store);
    $orderLine = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ], [
        'tracking_company' => 'DHL',
        'tracking_number' => 'DHL123456',
        'tracking_url' => 'https://tracking.dhl.com/DHL123456',
    ]);

    expect($fulfillment->tracking_company)->toBe('DHL');
    expect($fulfillment->tracking_number)->toBe('DHL123456');
    expect($fulfillment->tracking_url)->toBe('https://tracking.dhl.com/DHL123456');
});

it('transitions pending to shipped to delivered', function () {
    $order = createPaidOrderForFulfillment($this->store);
    $orderLine = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);

    $this->fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => 'SHIP001',
    ]);
    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped);
    expect($fulfillment->shipped_at)->not->toBeNull();

    $this->fulfillmentService->markAsDelivered($fulfillment);
    $fulfillment->refresh();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
    expect($fulfillment->delivered_at)->not->toBeNull();
});

it('prevents fulfilling more than ordered quantity', function () {
    $order = createPaidOrderForFulfillment($this->store, [
        ['quantity' => 2, 'price' => 2500],
    ]);
    $orderLine = $order->lines->first();

    expect(fn () => $this->fulfillmentService->create($order, [
        $orderLine->id => 5,
    ]))->toThrow(InvalidArgumentException::class);
});

it('blocks fulfillment when financial_status is pending', function () {
    $order = createPaidOrderForFulfillment($this->store);
    $order->update(['financial_status' => FinancialStatus::Pending]);
    $orderLine = $order->lines->first();

    expect(fn () => $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]))->toThrow(FulfillmentGuardException::class);
});

it('allows fulfillment when financial_status is paid', function () {
    $order = createPaidOrderForFulfillment($this->store);
    $orderLine = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);

    expect($fulfillment)->not->toBeNull();
});

it('allows fulfillment when financial_status is partially_refunded', function () {
    $order = createPaidOrderForFulfillment($this->store);
    $order->update(['financial_status' => FinancialStatus::PartiallyRefunded]);
    $orderLine = $order->lines->first();

    $fulfillment = $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);

    expect($fulfillment)->not->toBeNull();
});

it('auto-fulfills digital products on payment confirmation', function () {
    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'order_number' => (string) fake()->unique()->numberBetween(5000, 99999),
        'email' => 'digital@example.com',
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_method' => PaymentMethod::BankTransfer,
        'total_amount' => 5000,
        'subtotal_amount' => 5000,
        'placed_at' => now(),
    ]);

    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 1,
        'policy' => InventoryPolicy::Deny,
    ]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
        'requires_shipping' => false,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'method' => PaymentMethod::BankTransfer,
        'provider' => 'mock',
        'provider_payment_id' => 'mock_digital',
        'amount' => 5000,
        'status' => PaymentStatus::Pending,
    ]);

    $orderService = app(OrderService::class);
    $orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->status)->toBe(OrderStatus::Fulfilled);
    expect($order->fulfillments)->toHaveCount(1);
});

it('dispatches OrderFulfilled event when fully fulfilled', function () {
    Event::fake([OrderFulfilled::class]);

    $order = createPaidOrderForFulfillment($this->store);
    $orderLine = $order->lines->first();

    $this->fulfillmentService->create($order, [
        $orderLine->id => $orderLine->quantity,
    ]);

    Event::assertDispatched(OrderFulfilled::class);
});
