<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\CancelUnpaidBankTransferOrders;
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
});

function createBankTransferOrder($store): Order
{
    $cart = app(CartService::class)->create($store);
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 4000,
        'requires_shipping' => false,
    ]);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout = $checkoutService->setAddress($checkout, [
        'email' => 'bank@example.com',
        'shipping_address' => [
            'first_name' => 'Max',
            'last_name' => 'Mueller',
            'address1' => '789 Elm St',
            'city' => 'Hamburg',
            'country' => 'DE',
            'postal_code' => '20095',
        ],
    ]);
    $checkout = $checkoutService->setShippingMethod($checkout, null);
    $checkout = $checkoutService->selectPaymentMethod($checkout, 'bank_transfer');

    return app(OrderService::class)->completeCheckout($checkout, []);
}

it('confirms a bank transfer payment and updates order to paid', function () {
    $order = createBankTransferOrder($this->store);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    // Digital orders get auto-fulfilled after bank transfer confirmation
    expect($order->financial_status)->toBe(FinancialStatus::Paid);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);
});

it('auto-fulfills digital bank transfer orders after confirmation', function () {
    $order = createBankTransferOrder($this->store);

    $this->orderService->confirmBankTransferPayment($order);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->fulfillments)->toHaveCount(1);
});

it('rejects confirmation for non-bank-transfer orders', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
        'financial_status' => FinancialStatus::Paid,
    ]);

    $this->orderService->confirmBankTransferPayment($order);
})->throws(\RuntimeException::class, 'does not use bank transfer');

it('rejects confirmation for already processed orders', function () {
    $order = createBankTransferOrder($this->store);
    $this->orderService->confirmBankTransferPayment($order);
    $order->refresh();

    $this->orderService->confirmBankTransferPayment($order);
})->throws(\RuntimeException::class, 'already been processed');

it('cancels unpaid bank transfer orders after 7 days', function () {
    $order = createBankTransferOrder($this->store);

    // Backdate the order to 8 days ago
    $order->update(['placed_at' => now()->subDays(8)]);

    $job = new CancelUnpaidBankTransferOrders;
    $job();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->financial_status)->toBe(FinancialStatus::Voided);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Failed);
});

it('does not cancel bank transfer orders within 7 days', function () {
    $order = createBankTransferOrder($this->store);

    // Order is just placed (within 7 days)
    $job = new CancelUnpaidBankTransferOrders;
    $job();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending);
});
