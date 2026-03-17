<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Crypt;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->orderService = app(OrderService::class);
    $this->checkoutService = app(CheckoutService::class);
    $this->cartService = app(CartService::class);
});

function prepareCheckoutForPayment($store, string $paymentMethod = 'credit_card'): \App\Models\Checkout
{
    $cart = app(CartService::class)->create($store);
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 3000,
        'requires_shipping' => false,
    ]);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout = $checkoutService->setAddress($checkout, [
        'email' => 'buyer@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address1' => '456 Oak Ave',
            'city' => 'Munich',
            'country' => 'DE',
            'postal_code' => '80331',
        ],
    ]);
    $checkout = $checkoutService->setShippingMethod($checkout, null);
    $checkout = $checkoutService->selectPaymentMethod($checkout, $paymentMethod);

    return $checkout;
}

it('creates a payment record with correct amount on credit card checkout', function () {
    $checkout = prepareCheckoutForPayment($this->store);
    $order = $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $order->load('payments');
    expect($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->amount)->toBe($order->total_amount)
        ->and($order->payments->first()->method)->toBe(PaymentMethod::CreditCard)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Captured)
        ->and($order->payments->first()->provider)->toBe('mock');
});

it('creates a pending payment for bank transfer orders', function () {
    $checkout = prepareCheckoutForPayment($this->store, 'bank_transfer');
    $order = $this->orderService->completeCheckout($checkout, []);

    $order->load('payments');
    expect($order->payments->first()->status)->toBe(PaymentStatus::Pending)
        ->and($order->payments->first()->method)->toBe(PaymentMethod::BankTransfer)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending);
});

it('stores encrypted raw payment response', function () {
    $checkout = prepareCheckoutForPayment($this->store);
    $order = $this->orderService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $payment = $order->payments()->first();
    expect($payment->raw_json_encrypted)->not->toBeNull();

    $decrypted = json_decode(Crypt::decryptString($payment->raw_json_encrypted), true);
    expect($decrypted)->toBeArray()
        ->and($decrypted['provider'])->toBe('mock')
        ->and($decrypted['last4'])->toBe('4242');
});

it('creates a captured payment for PayPal checkout', function () {
    $checkout = prepareCheckoutForPayment($this->store, 'paypal');
    $order = $this->orderService->completeCheckout($checkout, []);

    $order->load('payments');
    expect($order->payments->first()->status)->toBe(PaymentStatus::Captured)
        ->and($order->payments->first()->method)->toBe(PaymentMethod::Paypal)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid);
});

it('does not auto-fulfill bank transfer orders', function () {
    $checkout = prepareCheckoutForPayment($this->store, 'bank_transfer');
    $order = $this->orderService->completeCheckout($checkout, []);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->fulfillments)->toHaveCount(0);
});
