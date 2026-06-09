<?php

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Events\OrderCreated;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

it('creates an order from a completed checkout', function () {
    $checkout = createPaymentSelectedCheckout($this->store, 'bank_transfer', quantity: 2);

    $order = $this->checkoutService->completeCheckout($checkout);

    $this->assertDatabaseHas('orders', [
        'id' => $order->getKey(),
        'store_id' => $this->store->getKey(),
        'status' => 'pending',
        'subtotal_amount' => 5000,
        'shipping_amount' => 499,
        'total_amount' => 5499,
    ]);
});

it('generates sequential order numbers per store', function () {
    $orderNumbers = [];

    foreach (range(1, 3) as $i) {
        $checkout = createPaymentSelectedCheckout($this->store);
        $orderNumbers[] = $this->checkoutService
            ->completeCheckout($checkout, ['card_number' => '4242424242424242'])
            ->order_number;
    }

    expect($orderNumbers)->toBe(['#1001', '#1002', '#1003']);
});

it('creates order lines with snapshots', function () {
    $checkout = createPaymentSelectedCheckout($this->store, quantity: 2, variantAttributes: ['sku' => 'SNAP-001']);

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $lines = $order->lines;
    expect($lines)->toHaveCount(1);
    expect($lines->first()->title_snapshot)->not->toBeEmpty();
    expect($lines->first()->sku_snapshot)->not->toBeEmpty();
    expect($lines->first()->quantity)->toBe(2);
});

it('commits inventory on order creation', function () {
    $checkout = createPaymentSelectedCheckout($this->store, quantity: 3, quantityOnHand: 10);

    $item = $checkout->cart->lines()->first()->variant->inventoryItem;
    expect($item->refresh()->quantity_reserved)->toBe(3);

    $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(7);
    expect($item->quantity_reserved)->toBe(0);
});

it('marks cart as converted', function () {
    $checkout = createPaymentSelectedCheckout($this->store);

    $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($checkout->cart->refresh()->status)->toBe(CartStatus::Converted);
});

it('dispatches OrderCreated event', function () {
    $checkout = createPaymentSelectedCheckout($this->store);

    Event::fake([OrderCreated::class]);

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    Event::assertDispatched(
        OrderCreated::class,
        fn (OrderCreated $event): bool => $event->order->is($order),
    );
});

it('preserves order data when product is deleted', function () {
    $checkout = createPaymentSelectedCheckout($this->store, variantAttributes: ['sku' => 'SNAP-002']);

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $product = $order->lines->first()->variant->product;
    app(ProductService::class)->transitionStatus($product, ProductStatus::Archived);

    $line = $order->refresh()->lines->first();
    expect($line->title_snapshot)->not->toBeEmpty();
    expect($line->sku_snapshot)->not->toBeEmpty();
});

it('links order to customer when authenticated', function () {
    $customer = Customer::factory()->for($this->store)->create();

    $checkout = createPaymentSelectedCheckout($this->store, customer: $customer);

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->customer_id)->toBe($customer->getKey());
});

it('sets email from checkout on the order', function () {
    $checkout = createPaymentSelectedCheckout($this->store, email: 'test@example.com');

    $order = $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->email)->toBe('test@example.com');
    expect(Order::query()->find($order->getKey())->email)->toBe('test@example.com');
});
