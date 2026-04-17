<?php

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\Payments\MockPaymentProvider;

function createPaymentTestContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
        'rate' => $rate,
    ]);
}

function processCheckoutToPaymentSelected(array $ctx, string $paymentMethod = 'credit_card'): \App\Models\Checkout
{
    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($ctx['cart']);

    $checkout = $service->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Los Angeles',
            'province_code' => 'CA',
            'country_code' => 'US',
            'postal_code' => '90001',
        ],
    ]);

    $checkout = $service->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $service->selectPaymentMethod($checkout, $paymentMethod);

    return $checkout;
}

it('processes credit card payment and creates order as paid', function () {
    $ctx = createPaymentTestContext();
    $checkout = processCheckoutToPaymentSelected($ctx, 'credit_card');

    $service = app(CheckoutService::class);
    $order = $service->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard);
});

it('processes PayPal payment and creates order as paid', function () {
    $ctx = createPaymentTestContext();
    $checkout = processCheckoutToPaymentSelected($ctx, 'paypal');

    $service = app(CheckoutService::class);
    $order = $service->completeCheckout($checkout, []);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::Paypal);
});

it('processes bank transfer and creates order as pending', function () {
    $ctx = createPaymentTestContext();
    $checkout = processCheckoutToPaymentSelected($ctx, 'bank_transfer');

    $service = app(CheckoutService::class);
    $order = $service->completeCheckout($checkout, []);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payment_method)->toBe(PaymentMethod::BankTransfer);
});

it('resolves MockPaymentProvider from container', function () {
    $provider = app(PaymentProvider::class);

    expect($provider)->toBeInstanceOf(MockPaymentProvider::class);
});

it('creates a payment record with correct method', function () {
    $ctx = createPaymentTestContext();
    $checkout = processCheckoutToPaymentSelected($ctx, 'credit_card');

    $service = app(CheckoutService::class);
    $order = $service->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $payment = $order->payments()->first();

    expect($payment)->not->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->provider)->toBe('mock')
        ->and($payment->provider_payment_id)->toStartWith('mock_');
});
