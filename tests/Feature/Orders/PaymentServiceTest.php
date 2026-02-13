<?php

use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPaid;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(OrderService::class);
});

test('credit card charge flow creates captured payment', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    $inventory = InventoryItem::factory()->withStock(50, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_discount_amount' => 0,
        'line_total_amount' => 1000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'credit_card',
        'email' => 'test@test.com',
        'totals_json' => ['subtotal' => 1000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 1000, 'currency' => 'USD'],
    ]);

    app(\App\Services\InventoryService::class)->reserve($inventory, 1);

    $order = $this->service->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);
    expect($payment->method)->toBe(PaymentMethod::CreditCard);
    expect($payment->amount)->toBe(1000);
    expect($payment->provider)->toBe('mock');
    expect($payment->provider_payment_id)->toStartWith('mock_');
});

test('bank transfer confirmation flow commits inventory', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 3000]);
    $inventory = InventoryItem::factory()->withStock(20, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 3000,
        'line_subtotal_amount' => 6000,
        'line_discount_amount' => 0,
        'line_total_amount' => 6000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'bank_transfer',
        'email' => 'bank@test.com',
        'totals_json' => ['subtotal' => 6000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 6000, 'currency' => 'USD'],
    ]);

    app(\App\Services\InventoryService::class)->reserve($inventory, 2);

    // Create order (pending)
    $order = $this->service->createFromCheckout($checkout, []);

    expect($order->financial_status)->toBe(FinancialStatus::Pending);

    // Inventory still reserved
    $inventory->refresh();
    expect($inventory->quantity_on_hand)->toBe(20);
    expect($inventory->quantity_reserved)->toBe(2);

    // Admin confirms payment
    $order->refresh();
    $this->service->confirmPayment($order);

    $inventory->refresh();
    expect($inventory->quantity_on_hand)->toBe(18);
    expect($inventory->quantity_reserved)->toBe(0);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->status)->toBe(OrderStatus::Paid);

    Event::assertDispatched(OrderPaid::class);
});

test('cancel unpaid bank transfer orders job cancels old orders', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $inventory = InventoryItem::factory()->withStock(50, 5)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    // Old unpaid order (8 days ago)
    $oldOrder = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'placed_at' => now()->subDays(8),
    ]);
    $oldOrder->lines()->create([
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Test',
        'quantity' => 5,
        'unit_price_amount' => 1000,
        'total_amount' => 5000,
    ]);
    Payment::factory()->pending()->create(['order_id' => $oldOrder->id, 'amount' => 5000]);

    // Recent unpaid order (2 days ago) - should NOT be cancelled
    $recentOrder = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'placed_at' => now()->subDays(2),
    ]);
    Payment::factory()->pending()->create(['order_id' => $recentOrder->id]);

    $job = new CancelUnpaidBankTransferOrders;
    $job->handle(app(\App\Services\InventoryService::class));

    $oldOrder->refresh();
    expect($oldOrder->status)->toBe(OrderStatus::Cancelled);
    expect($oldOrder->financial_status)->toBe(FinancialStatus::Voided);

    // Inventory released
    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(0);

    // Recent order untouched
    $recentOrder->refresh();
    expect($recentOrder->status)->toBe(OrderStatus::Pending);

    Event::assertDispatched(OrderCancelled::class);
});

test('cancel unpaid job respects configurable days from store settings', function () {
    Event::fake();

    // Set custom cancel days to 3
    StoreSettings::create([
        'store_id' => $this->store->id,
        'settings_json' => ['bank_transfer_cancel_days' => 3],
    ]);

    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'placed_at' => now()->subDays(4),
    ]);
    Payment::factory()->pending()->create(['order_id' => $order->id]);

    $job = new CancelUnpaidBankTransferOrders;
    $job->handle(app(\App\Services\InventoryService::class));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
});

test('payment record stores encrypted raw json', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    $inventory = InventoryItem::factory()->withStock(50, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_discount_amount' => 0,
        'line_total_amount' => 1000,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'credit_card',
        'email' => 'enc@test.com',
        'totals_json' => ['subtotal' => 1000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 1000, 'currency' => 'USD'],
    ]);

    app(\App\Services\InventoryService::class)->reserve($inventory, 1);

    $order = $this->service->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $payment = $order->payments()->first();
    expect($payment->raw_json_encrypted)->not->toBeNull();

    // The encrypted cast should allow reading back as decoded value
    $raw = $payment->raw_json_encrypted;
    expect($raw)->toBeString();
});

test('multiple orders in same store have sequential numbers', function () {
    Event::fake();

    $numbers = [];
    for ($i = 0; $i < 3; $i++) {
        $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
        $inventory = InventoryItem::factory()->withStock(50, 0)->create([
            'store_id' => $this->store->id,
            'variant_id' => $variant->id,
        ]);

        $cart = Cart::factory()->create(['store_id' => $this->store->id]);
        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_amount' => 1000,
            'line_subtotal_amount' => 1000,
            'line_discount_amount' => 0,
            'line_total_amount' => 1000,
        ]);

        $checkout = Checkout::factory()->create([
            'store_id' => $this->store->id,
            'cart_id' => $cart->id,
            'status' => CheckoutStatus::PaymentSelected,
            'payment_method' => 'paypal',
            'email' => "test{$i}@test.com",
            'totals_json' => ['subtotal' => 1000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 1000, 'currency' => 'USD'],
        ]);

        app(\App\Services\InventoryService::class)->reserve($inventory, 1);

        $order = $this->service->createFromCheckout($checkout, []);
        $numbers[] = $order->order_number;
    }

    expect($numbers)->toBe(['#1001', '#1002', '#1003']);
});
