<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->orderService = app(OrderService::class);
    $this->checkoutService = app(CheckoutService::class);
    $this->cartService = app(CartService::class);
});

function prepareCheckout($store, string $paymentMethod = 'credit_card', bool $requiresShipping = false): \App\Models\Checkout
{
    $cart = app(CartService::class)->create($store);
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'requires_shipping' => $requiresShipping,
    ]);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout = $checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = $checkoutService->setShippingMethod($checkout, null);
    $checkout = $checkoutService->selectPaymentMethod($checkout, $paymentMethod);

    return $checkout;
}

it('creates an order from a completed checkout with credit card', function () {
    $checkout = prepareCheckout($this->store, 'credit_card', true);

    $order = $this->orderService->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->store_id)->toBe($this->store->id)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($order->total_amount)->toBeGreaterThan(0)
        ->and($order->email)->toBe('test@example.com');
});

it('generates sequential order numbers starting at 1001', function () {
    $orderNumber = $this->orderService->generateOrderNumber($this->store->id);
    expect($orderNumber)->toBe('#1001');

    $checkout = prepareCheckout($this->store);
    $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $orderNumber2 = $this->orderService->generateOrderNumber($this->store->id);
    expect($orderNumber2)->toBe('#1002');
});

it('creates order lines from cart lines', function () {
    $checkout = prepareCheckout($this->store);
    $order = $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $order->load('lines');
    expect($order->lines)->toHaveCount(1)
        ->and($order->lines->first()->quantity)->toBe(2)
        ->and($order->lines->first()->unit_price_amount)->toBe(2500);
});

it('creates a payment record linked to the order', function () {
    $checkout = prepareCheckout($this->store);
    $order = $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $order->load('payments');
    expect($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Captured)
        ->and($order->payments->first()->amount)->toBe($order->total_amount)
        ->and($order->payments->first()->provider)->toBe('mock');
});

it('marks cart as converted after checkout', function () {
    $checkout = prepareCheckout($this->store);
    $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $cart = Cart::query()->withoutGlobalScopes()->find($checkout->cart_id);
    expect($cart->status)->toBe(CartStatus::Converted);
});

it('marks checkout as completed after order creation', function () {
    $checkout = prepareCheckout($this->store);
    $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $checkout->refresh();
    expect($checkout->status)->toBe(CheckoutStatus::Completed);
});

it('throws PaymentFailedException for declined card', function () {
    $checkout = prepareCheckout($this->store);

    $this->orderService->completeCheckout($checkout, [
        'card_number' => '4000000000000002',
    ]);
})->throws(PaymentFailedException::class);

it('returns existing order on idempotent retry of completed checkout', function () {
    $checkout = prepareCheckout($this->store);
    $order1 = $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $checkout->refresh();
    $order2 = $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);
    expect($order2->id)->toBe($order1->id);
});

it('auto-fulfills digital orders on instant capture', function () {
    $checkout = prepareCheckout($this->store, 'credit_card', false);
    $order = $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->fulfillments)->toHaveCount(1);
});
