<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(OrderService::class);
});

function createCheckoutWithCartLine(Store $store, string $paymentMethod = 'credit_card', int $priceAmount = 2500, int $quantity = 2): array
{
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $priceAmount,
    ]);
    $inventory = InventoryItem::factory()->withStock(100, 0)->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $subtotal = $priceAmount * $quantity;
    $cartLine = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => $priceAmount,
        'line_subtotal_amount' => $subtotal,
        'line_discount_amount' => 0,
        'line_total_amount' => $subtotal,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => $paymentMethod,
        'email' => 'customer@example.com',
        'shipping_address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'postal_code' => '10001',
        ],
        'billing_address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'postal_code' => '10001',
        ],
        'totals_json' => [
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping' => 500,
            'tax_total' => 0,
            'total' => $subtotal + 500,
            'currency' => 'USD',
        ],
    ]);

    return [$checkout, $cart, $cartLine, $product, $variant, $inventory];
}

test('createFromCheckout creates order with credit card', function () {
    Event::fake();

    [$checkout, $cart, $cartLine, $product, $variant, $inventory] = createCheckoutWithCartLine($this->store);

    // Reserve inventory first (simulating what selectPaymentMethod does)
    app(\App\Services\InventoryService::class)->reserve($inventory, $cartLine->quantity);

    $order = $this->service->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
    expect($order->payment_method)->toBe(PaymentMethod::CreditCard);
    expect($order->email)->toBe('customer@example.com');
    expect($order->order_number)->toStartWith('#');
    expect($order->total_amount)->toBe(5500);
    expect($order->placed_at)->not->toBeNull();

    // Verify order line created
    expect($order->lines)->toHaveCount(1);
    $line = $order->lines->first();
    expect($line->title_snapshot)->toContain($product->title);
    expect($line->quantity)->toBe(2);
    expect($line->unit_price_amount)->toBe(2500);

    // Verify payment record
    $payment = $order->payments->first();
    expect($payment->provider)->toBe('mock');
    expect($payment->method)->toBe(PaymentMethod::CreditCard);
    expect($payment->status)->toBe(PaymentStatus::Captured);

    // Verify cart marked as converted
    $cart->refresh();
    expect($cart->status)->toBe(CartStatus::Converted);

    // Verify checkout marked as completed
    $checkout->refresh();
    expect($checkout->status)->toBe(CheckoutStatus::Completed);

    // Verify inventory committed
    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(0);
    expect($inventory->quantity_on_hand)->toBe(98);

    Event::assertDispatched(OrderCreated::class);
});

test('createFromCheckout with bank transfer creates pending order', function () {
    Event::fake();

    [$checkout, $cart, $cartLine, $product, $variant, $inventory] = createCheckoutWithCartLine($this->store, 'bank_transfer');

    // Reserve inventory
    app(\App\Services\InventoryService::class)->reserve($inventory, $cartLine->quantity);

    $order = $this->service->createFromCheckout($checkout, []);

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->financial_status)->toBe(FinancialStatus::Pending);
    expect($order->payment_method)->toBe(PaymentMethod::BankTransfer);

    // Verify payment is pending
    $payment = $order->payments->first();
    expect($payment->status)->toBe(PaymentStatus::Pending);

    // Inventory should still be reserved (not committed)
    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(2);
    expect($inventory->quantity_on_hand)->toBe(100);

    Event::assertDispatched(OrderCreated::class);
});

test('createFromCheckout with paypal creates paid order', function () {
    Event::fake();

    [$checkout, $cart, $cartLine, $product, $variant, $inventory] = createCheckoutWithCartLine($this->store, 'paypal');

    app(\App\Services\InventoryService::class)->reserve($inventory, $cartLine->quantity);

    $order = $this->service->createFromCheckout($checkout, []);

    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->payment_method)->toBe(PaymentMethod::Paypal);

    $payment = $order->payments->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);
    expect($payment->method)->toBe(PaymentMethod::Paypal);
});

test('createFromCheckout with declined card throws and releases inventory', function () {
    [$checkout, $cart, $cartLine, $product, $variant, $inventory] = createCheckoutWithCartLine($this->store);

    app(\App\Services\InventoryService::class)->reserve($inventory, $cartLine->quantity);

    expect(fn () => $this->service->createFromCheckout($checkout, ['card_number' => '4000000000000002']))
        ->toThrow(PaymentFailedException::class);

    // Inventory should be released
    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(0);
});

test('generateOrderNumber returns sequential numbers starting at 1001', function () {
    $number1 = $this->service->generateOrderNumber($this->store);
    expect($number1)->toBe('#1001');

    // Create an order with that number
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#1001',
    ]);

    $number2 = $this->service->generateOrderNumber($this->store);
    expect($number2)->toBe('#1002');
});

test('generateOrderNumber is sequential per store', function () {
    $store2 = Store::factory()->create();

    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#1005',
    ]);

    $number1 = $this->service->generateOrderNumber($this->store);
    expect($number1)->toBe('#1006');

    $number2 = $this->service->generateOrderNumber($store2);
    expect($number2)->toBe('#1001');
});

test('cancel order marks it as cancelled', function () {
    Event::fake();

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    $result = $this->service->cancel($order, 'Customer request');

    expect($result->status)->toBe(OrderStatus::Cancelled);
    Event::assertDispatched(OrderCancelled::class);
});

test('cancel pending bank transfer order releases inventory', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $inventory = InventoryItem::factory()->withStock(100, 5)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
    ]);

    $order->lines()->create([
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 5,
        'unit_price_amount' => 1000,
        'total_amount' => 5000,
    ]);

    $result = $this->service->cancel($order);

    expect($result->status)->toBe(OrderStatus::Cancelled);
    expect($result->financial_status)->toBe(FinancialStatus::Voided);

    $inventory->refresh();
    expect($inventory->quantity_reserved)->toBe(0);
});

test('cannot cancel partially fulfilled order', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'fulfillment_status' => FulfillmentStatus::Partial,
    ]);

    expect(fn () => $this->service->cancel($order))
        ->toThrow(InvalidArgumentException::class);
});

test('cannot cancel fully fulfilled order', function () {
    $order = Order::factory()->fulfilled()->create([
        'store_id' => $this->store->id,
    ]);

    expect(fn () => $this->service->cancel($order))
        ->toThrow(InvalidArgumentException::class);
});

test('confirmPayment transitions bank transfer order to paid', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $inventory = InventoryItem::factory()->withStock(100, 5)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
    ]);

    $order->lines()->create([
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 5,
        'unit_price_amount' => 1000,
        'total_amount' => 5000,
    ]);

    \App\Models\Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'amount' => $order->total_amount,
    ]);

    $result = $this->service->confirmPayment($order);

    expect($result->status)->toBe(OrderStatus::Paid);
    expect($result->financial_status)->toBe(FinancialStatus::Paid);

    // Inventory committed
    $inventory->refresh();
    expect($inventory->quantity_on_hand)->toBe(95);
    expect($inventory->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderPaid::class);
});

test('confirmPayment on non-bank-transfer order throws', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
    ]);

    expect(fn () => $this->service->confirmPayment($order))
        ->toThrow(InvalidArgumentException::class);
});

test('confirmPayment on already paid order throws', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::BankTransfer,
        'financial_status' => FinancialStatus::Paid,
    ]);

    expect(fn () => $this->service->confirmPayment($order))
        ->toThrow(InvalidArgumentException::class);
});

test('createFromCheckout with customer links order to customer', function () {
    Event::fake();

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    [$checkout, $cart] = createCheckoutWithCartLine($this->store);
    $checkout->update(['customer_id' => $customer->id]);

    // Reserve inventory
    $cartLine = $cart->lines->first();
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $cartLine->variant_id)->first();
    app(\App\Services\InventoryService::class)->reserve($inventory, $cartLine->quantity);

    $order = $this->service->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->customer_id)->toBe($customer->id);
});

test('createFromCheckout with discount increments usage count', function () {
    Event::fake();

    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'usage_count' => 0,
    ]);

    [$checkout, $cart, $cartLine, $product, $variant, $inventory] = createCheckoutWithCartLine($this->store);
    $checkout->update(['discount_code' => 'SAVE10']);

    app(\App\Services\InventoryService::class)->reserve($inventory, $cartLine->quantity);

    $this->service->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    $discount->refresh();
    expect($discount->usage_count)->toBe(1);
});

test('auto fulfills digital product order', function () {
    Event::fake();

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->digital()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);
    $inventory = InventoryItem::factory()->withStock(100, 0)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'credit_card',
        'email' => 'digital@example.com',
        'totals_json' => ['subtotal' => 2500, 'discount' => 0, 'shipping' => 0, 'tax_total' => 0, 'total' => 2500, 'currency' => 'USD'],
    ]);

    app(\App\Services\InventoryService::class)->reserve($inventory, 1);

    $order = $this->service->createFromCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->status)->toBe(OrderStatus::Fulfilled);
    expect($order->fulfillments)->toHaveCount(1);

    $fulfillment = $order->fulfillments->first();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});
