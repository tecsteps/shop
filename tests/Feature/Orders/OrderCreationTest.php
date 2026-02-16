<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Events\OrderCreated;
use App\Models\Checkout;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('creates an order from a completed checkout', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store'], 2500, 2, 100);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'email' => 'buyer@example.com',
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 5000],
    ]);

    Event::fake();
    $orderService = app(OrderService::class);
    $order = $orderService->createFromCheckout($checkout);

    expect($order->email)->toBe('buyer@example.com')
        ->and($order->total)->toBe(5000)
        ->and($order->lines)->toHaveCount(1);

    Event::assertDispatched(OrderCreated::class);
});

it('generates sequential order numbers per store', function () {
    $orderService = app(OrderService::class);

    $n1 = $orderService->generateOrderNumber($this->ctx['store']);
    expect($n1)->toBe('#1001');

    Order::factory()->for($this->ctx['store'])->create(['order_number' => '#1001']);
    $n2 = $orderService->generateOrderNumber($this->ctx['store']);
    expect($n2)->toBe('#1002');
});

it('creates order lines with snapshots', function () {
    ['cart' => $cart] = createCartWithProduct(
        $this->ctx['store'],
        2500,
        1,
        100,
        ['title' => 'My Product'],
        ['sku' => 'SKU-001'],
    );

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);
    $line = $order->lines->first();

    expect($line->title_snapshot)->toBe('My Product')
        ->and($line->sku_snapshot)->toBe('SKU-001');
});

it('marks cart as converted', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store'], 2500, 1, 100);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    app(OrderService::class)->createFromCheckout($checkout);

    expect($cart->fresh()->status)->toBe(CartStatus::Converted);
});

it('sets email from checkout on the order', function () {
    ['cart' => $cart] = createCartWithProduct($this->ctx['store'], 2500, 1, 100);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'email' => 'test@example.com',
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500],
    ]);

    $order = app(OrderService::class)->createFromCheckout($checkout);
    expect($order->email)->toBe('test@example.com');
});
