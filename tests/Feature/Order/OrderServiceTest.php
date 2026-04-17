<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->orderService = app(OrderService::class);
});

function createCheckoutWithItems(Store $store, string $paymentMethod = 'credit_card', int $quantity = 1): Checkout
{
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $product = Product::withoutEvents(function () use ($store) {
        return Product::factory()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ]);
    });

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'is_default' => true,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => $quantity,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cart = Cart::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'currency' => 'USD',
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500 * $quantity,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500 * $quantity,
    ]);

    return Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => $paymentMethod,
        'email' => 'test@example.com',
        'shipping_address_json' => ['first_name' => 'John', 'last_name' => 'Doe', 'city' => 'NYC'],
        'billing_address_json' => ['first_name' => 'John', 'last_name' => 'Doe', 'city' => 'NYC'],
        'totals_json' => [
            'subtotal' => 2500 * $quantity,
            'discount' => 0,
            'shipping' => 500,
            'tax' => 200,
            'total' => (2500 * $quantity) + 700,
        ],
    ]);
}

it('creates an order from checkout with credit card', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($order->total_amount)->toBe(3200)
        ->and($order->email)->toBe('test@example.com')
        ->and($order->order_number)->toStartWith('#');

    Event::assertDispatched(OrderCreated::class);
    Event::assertDispatched(OrderPaid::class);
});

it('creates order lines with snapshots', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($order->lines)->toHaveCount(1);

    $line = $order->lines->first();
    expect($line->title_snapshot)->not->toBeEmpty()
        ->and($line->quantity)->toBe(1)
        ->and($line->unit_price_amount)->toBe(2500)
        ->and($line->product_id)->not->toBeNull()
        ->and($line->variant_id)->not->toBeNull();
});

it('creates a payment record', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $payment = $order->payments->first();
    expect($payment)->not->toBeNull()
        ->and($payment->provider)->toBe('mock')
        ->and($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->amount)->toBe(3200)
        ->and($payment->provider_payment_id)->toStartWith('mock_');
});

it('commits inventory on credit card order', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $variant = $order->lines->first()->variant;
    $inventory = $variant->inventoryItem->fresh();

    // Inventory was: on_hand=10, reserved=1
    // After commit: on_hand=9, reserved=0
    expect($inventory->quantity_on_hand)->toBe(9)
        ->and($inventory->quantity_reserved)->toBe(0);
});

it('marks cart as converted', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $order = $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $cart = $checkout->cart->fresh();
    expect($cart->status)->toBe(CartStatus::Converted);
});

it('marks checkout as completed', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Completed);
});

it('creates bank transfer order with pending status', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store, 'bank_transfer');
    $order = $this->orderService->createFromCheckout($checkout);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payment_method)->toBe(PaymentMethod::BankTransfer);

    $payment = $order->payments->first();
    expect($payment->status)->toBe(PaymentStatus::Pending);

    Event::assertDispatched(OrderCreated::class);
    Event::assertNotDispatched(OrderPaid::class);
});

it('keeps inventory reserved for bank transfer orders', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store, 'bank_transfer');
    $order = $this->orderService->createFromCheckout($checkout);

    $variant = $order->lines->first()->variant;
    $inventory = $variant->inventoryItem->fresh();

    // Inventory stays reserved, not committed
    // on_hand=10 (unchanged), reserved=1 (unchanged)
    expect($inventory->quantity_on_hand)->toBe(10)
        ->and($inventory->quantity_reserved)->toBe(1);
});

it('throws exception for declined credit card', function () {
    $checkout = createCheckoutWithItems($this->store);

    expect(fn () => $this->orderService->createFromCheckout($checkout, [
        'card_number' => '4000000000000002',
    ]))->toThrow(PaymentFailedException::class, 'card_declined');
});

it('releases inventory on payment failure', function () {
    $checkout = createCheckoutWithItems($this->store);

    try {
        $this->orderService->createFromCheckout($checkout, [
            'card_number' => '4000000000000002',
        ]);
    } catch (PaymentFailedException) {
        // expected
    }

    $variant = CartLine::where('cart_id', $checkout->cart_id)->first()->variant;
    $inventory = $variant->inventoryItem->fresh();

    // reserved was 1, should be released back to 0
    expect($inventory->quantity_reserved)->toBe(0);
});

it('generates sequential order numbers', function () {
    Event::fake();

    $checkout1 = createCheckoutWithItems($this->store);
    $order1 = $this->orderService->createFromCheckout($checkout1, ['card_number' => '4242424242424242']);

    $checkout2 = createCheckoutWithItems($this->store);
    $order2 = $this->orderService->createFromCheckout($checkout2, ['card_number' => '4242424242424242']);

    expect($order1->order_number)->toBe('#1001')
        ->and($order2->order_number)->toBe('#1002');
});

it('cancels an unfulfilled order', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $this->orderService->cancel($order, 'Customer requested cancellation');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);

    Event::assertDispatched(OrderCancelled::class);
});

it('restocks inventory on cancellation of paid order', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store);
    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $variant = $order->lines->first()->variant;
    $inventoryBefore = $variant->inventoryItem->fresh()->quantity_on_hand;

    $this->orderService->cancel($order, 'cancelled');

    $inventoryAfter = $variant->inventoryItem->fresh()->quantity_on_hand;
    // After cancellation, on_hand should be restocked (was 9 after commit, now 10)
    expect($inventoryAfter)->toBe($inventoryBefore + 1);
});

it('confirms bank transfer payment', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store, 'bank_transfer');
    $order = $this->orderService->createFromCheckout($checkout);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid);

    $payment = $order->payments->first();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Captured);

    Event::assertDispatched(OrderPaid::class);
});

it('commits inventory on bank transfer confirmation', function () {
    Event::fake();

    $checkout = createCheckoutWithItems($this->store, 'bank_transfer');
    $order = $this->orderService->createFromCheckout($checkout);

    $variant = $order->lines->first()->variant;
    expect($variant->inventoryItem->fresh()->quantity_on_hand)->toBe(10);

    $this->orderService->confirmBankTransferPayment($order);

    $inventory = $variant->inventoryItem->fresh();
    expect($inventory->quantity_on_hand)->toBe(9)
        ->and($inventory->quantity_reserved)->toBe(0);
});

it('auto-fulfills digital products on credit card payment', function () {
    Event::fake();

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $product = Product::withoutEvents(fn () => Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]));

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'is_default' => true,
        'requires_shipping' => false,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
        'quantity_reserved' => 1,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'customer_id' => $customer->id]);
    CartLine::create([
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
        'customer_id' => $customer->id,
        'status' => CheckoutStatus::PaymentPending,
        'payment_method' => 'credit_card',
        'email' => 'digital@example.com',
        'totals_json' => ['subtotal' => 1000, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 1000],
    ]);

    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled)
        ->and($order->fulfillments)->toHaveCount(1);
});
