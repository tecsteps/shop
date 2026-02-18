<?php

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->orderService = app(OrderService::class);
});

it('creates an order from a completed checkout', function () {
    $checkout = createOrderCheckout($this->store);

    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->store_id)->toBe($this->store->id)
        ->and($order->total_amount)->toBe(6749)
        ->and($order->status)->toBe(OrderStatus::Paid);
});

it('generates sequential order numbers per store', function () {
    $orders = [];
    for ($i = 0; $i < 3; $i++) {
        $checkout = createOrderCheckout($this->store);
        $orders[] = $this->orderService->createFromCheckout($checkout, [
            'card_number' => '4242424242424242',
        ]);
    }

    expect($orders[0]->order_number)->toBe('#1001')
        ->and($orders[1]->order_number)->toBe('#1002')
        ->and($orders[2]->order_number)->toBe('#1003');
});

it('creates order lines with snapshots', function () {
    $checkout = createOrderCheckout($this->store, lineCount: 2);

    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $order->loadMissing('lines');
    expect($order->lines)->toHaveCount(2);

    foreach ($order->lines as $line) {
        expect($line->title_snapshot)->not->toBeEmpty()
            ->and($line->unit_price_amount)->toBeGreaterThan(0);
    }
});

it('commits inventory on order creation', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'EUR']);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    $item = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 3000,
        'line_discount_amount' => 0,
        'line_total_amount' => 3000,
    ]);
    $checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'payment_method' => 'credit_card',
        'email' => 'test@example.com',
        'totals_json' => ['subtotal' => 3000, 'discount' => 0, 'shipping' => 0, 'taxTotal' => 0, 'total' => 3000],
    ]);

    $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);
});

it('marks cart as converted', function () {
    $checkout = createOrderCheckout($this->store);

    $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $checkout->refresh();
    expect($checkout->cart->status)->toBe(CartStatus::Converted);
});

it('dispatches OrderCreated event', function () {
    Event::fake([OrderCreated::class]);

    $checkout = createOrderCheckout($this->store);

    $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    Event::assertDispatched(OrderCreated::class);
});

it('preserves order data when product is deleted', function () {
    $checkout = createOrderCheckout($this->store);
    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    // Archive the product
    $checkout->cart->lines->first()->variant->product->update(['status' => ProductStatus::Archived]);

    $order->refresh();
    $order->loadMissing('lines');
    expect($order->lines->first()->title_snapshot)->not->toBeEmpty();
});

it('links order to customer when authenticated', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $checkout = createOrderCheckout($this->store, customerId: $customer->id);

    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->customer_id)->toBe($customer->id);
});

it('sets email from checkout on the order', function () {
    $checkout = createOrderCheckout($this->store, email: 'test@example.com');

    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->email)->toBe('test@example.com');
});

function createOrderCheckout(
    \App\Models\Store $store,
    int $lineCount = 1,
    ?int $customerId = null,
    string $email = 'test@example.com',
): Checkout {
    $cart = Cart::factory()->create(['store_id' => $store->id, 'currency' => 'EUR']);
    $subtotal = 0;

    for ($i = 0; $i < $lineCount; $i++) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => 2500,
        ]);
        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 2,
        ]);
        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price_amount' => 2500,
            'line_subtotal_amount' => 5000,
            'line_discount_amount' => 0,
            'line_total_amount' => 5000,
        ]);
        $subtotal += 5000;
    }

    return Checkout::factory()->paymentSelected()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => $customerId,
        'payment_method' => 'credit_card',
        'email' => $email,
        'totals_json' => [
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping' => 799,
            'taxTotal' => 950,
            'total' => $subtotal + 799 + 950,
            'currency' => 'EUR',
        ],
    ]);
}
