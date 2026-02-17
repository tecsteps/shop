<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Exceptions\PaymentDeclinedException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->orderService = app(OrderService::class);
});

function createFullCheckout(Store $store, string $paymentMethod = 'credit_card'): Checkout
{
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 2,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    return Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => $paymentMethod,
        'email' => 'test@example.com',
        'shipping_address_json' => ['first_name' => 'John', 'last_name' => 'Doe', 'address1' => '123 Main St', 'city' => 'Berlin', 'country' => 'DE', 'postal_code' => '10115'],
        'billing_address_json' => ['first_name' => 'John', 'last_name' => 'Doe', 'address1' => '123 Main St', 'city' => 'Berlin', 'country' => 'DE', 'postal_code' => '10115'],
        'totals_json' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 799, 'tax_total' => 950, 'total' => 6749, 'currency' => 'EUR'],
    ]);
}

it('creates an order from checkout with credit card', function () {
    Event::fake();
    $checkout = createFullCheckout($this->store, 'credit_card');

    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($order->total_amount)->toBe(6749)
        ->and($order->order_number)->toStartWith('#');

    Event::assertDispatched(OrderCreated::class);
});

it('creates an order from checkout with paypal', function () {
    Event::fake();
    $checkout = createFullCheckout($this->store, 'paypal');

    $order = $this->orderService->createFromCheckout($checkout, []);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::Paypal);

    Event::assertDispatched(OrderCreated::class);
});

it('creates a pending order for bank transfer', function () {
    Event::fake();
    $checkout = createFullCheckout($this->store, 'bank_transfer');

    $order = $this->orderService->createFromCheckout($checkout, []);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payment_method)->toBe(PaymentMethod::BankTransfer);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Pending);

    Event::assertDispatched(OrderCreated::class);
});

it('creates order lines with snapshot data', function () {
    Event::fake();
    $checkout = createFullCheckout($this->store);

    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->lines)->toHaveCount(1);

    $line = $order->lines->first();
    expect($line->title_snapshot)->not->toBeEmpty()
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2500);
});

it('marks cart as converted after order creation', function () {
    Event::fake();
    $checkout = createFullCheckout($this->store);

    $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $checkout->refresh();
    $cart = $checkout->cart;
    $cart->refresh();

    expect($checkout->status)->toBe(CheckoutStatus::Completed)
        ->and($cart->status)->toBe(CartStatus::Converted);
});

it('creates payment record for the order', function () {
    Event::fake();
    $checkout = createFullCheckout($this->store);

    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $payment = $order->payments()->first();
    expect($payment)->not->toBeNull()
        ->and($payment->provider)->toBe('mock')
        ->and($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->amount)->toBe($order->total_amount)
        ->and($payment->provider_payment_id)->toStartWith('mock_');
});

it('throws PaymentDeclinedException on card decline', function () {
    $checkout = createFullCheckout($this->store);

    expect(fn () => $this->orderService->createFromCheckout($checkout, ['card_number' => '4000000000000002']))
        ->toThrow(PaymentDeclinedException::class);
});

it('generates sequential order numbers per store', function () {
    Event::fake();

    $checkout1 = createFullCheckout($this->store);
    $order1 = $this->orderService->createFromCheckout($checkout1, ['card_number' => '4242424242424242']);

    $checkout2 = createFullCheckout($this->store);
    $order2 = $this->orderService->createFromCheckout($checkout2, ['card_number' => '4242424242424242']);

    $num1 = (int) ltrim($order1->order_number, '#');
    $num2 = (int) ltrim($order2->order_number, '#');

    expect($num2)->toBe($num1 + 1);
});

it('commits inventory for instant capture payment methods', function () {
    Event::fake();
    $checkout = createFullCheckout($this->store);

    $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
    $inventoryItem = $cart->lines->first()->variant->inventoryItem;
    $originalOnHand = $inventoryItem->quantity_on_hand;
    $originalReserved = $inventoryItem->quantity_reserved;
    $quantity = $cart->lines->first()->quantity;

    $this->orderService->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $inventoryItem->refresh();
    expect($inventoryItem->quantity_on_hand)->toBe($originalOnHand - $quantity)
        ->and($inventoryItem->quantity_reserved)->toBe($originalReserved - $quantity);
});
