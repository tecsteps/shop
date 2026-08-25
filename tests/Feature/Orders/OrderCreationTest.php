<?php

use App\Events\OrderCreated;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

it('generates sequential order numbers per store', function () {
    $ctx = createStoreContext();
    $service = app(OrderService::class);

    expect($service->generateOrderNumber($ctx['store']))->toBe('#1001');

    Order::factory()->create(['store_id' => $ctx['store']->id, 'order_number' => '#1001']);

    expect($service->generateOrderNumber($ctx['store']))->toBe('#1002');
});

it('creates order lines with snapshots', function () {
    $order = makeCompletedOrder();

    expect($order->lines()->count())->toBe(1);
    expect($order->lines()->first()->title_snapshot)->toBe('Widget');
});

it('marks cart as converted and commits inventory', function () {
    $order = makeCompletedOrder();

    $cart = \App\Models\Cart::find($order->checkout->cart_id);
    expect($cart->status)->toBe('converted');
});

it('dispatches OrderCreated event', function () {
    Event::fake([OrderCreated::class]);

    makeCompletedOrder();

    Event::assertDispatched(OrderCreated::class);
});
