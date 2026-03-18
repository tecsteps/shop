<?php

use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;

function createPaymentServiceContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Payment Test Product',
        'handle' => 'payment-test-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'requires_shipping' => true,
        'weight_g' => 500,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 20,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'name' => 'DE',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    TaxSettings::create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    return array_merge($ctx, compact('product', 'variant', 'cart', 'zone', 'rate'));
}

function advanceToPaymentSelected(array $ctx, string $paymentMethod = 'credit_card'): \App\Models\Checkout
{
    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'payment@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkoutService->setShippingMethod($checkout->fresh(), $ctx['rate']->id);
    $checkoutService->selectPaymentMethod($checkout->fresh(), $paymentMethod);

    return $checkout->fresh();
}

it('processes credit card payment end to end', function () {
    $ctx = createPaymentServiceContext();
    $checkoutService = app(CheckoutService::class);
    $checkout = advanceToPaymentSelected($ctx);

    $order = $checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->lines)->toHaveCount(1);
});

it('processes PayPal payment end to end', function () {
    $ctx = createPaymentServiceContext();
    $checkoutService = app(CheckoutService::class);
    $checkout = advanceToPaymentSelected($ctx, 'paypal');

    $order = $checkoutService->completeCheckout($checkout, []);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::Paypal);
});

it('processes bank transfer payment end to end', function () {
    $ctx = createPaymentServiceContext();
    $checkoutService = app(CheckoutService::class);
    $checkout = advanceToPaymentSelected($ctx, 'bank_transfer');

    $order = $checkoutService->completeCheckout($checkout, []);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payment_method)->toBe(PaymentMethod::BankTransfer);
});

it('handles credit card decline gracefully', function () {
    $ctx = createPaymentServiceContext();
    $checkoutService = app(CheckoutService::class);
    $checkout = advanceToPaymentSelected($ctx);

    try {
        $checkoutService->completeCheckout($checkout, ['card_number' => '4000000000000002']);
        $this->fail('Expected PaymentFailedException');
    } catch (PaymentFailedException $e) {
        expect($e->errorCode)->toBe('card_declined');
    }
});

it('handles insufficient funds decline gracefully', function () {
    $ctx = createPaymentServiceContext();
    $checkoutService = app(CheckoutService::class);
    $checkout = advanceToPaymentSelected($ctx);

    try {
        $checkoutService->completeCheckout($checkout, ['card_number' => '4000000000009995']);
        $this->fail('Expected PaymentFailedException');
    } catch (PaymentFailedException $e) {
        expect($e->errorCode)->toBe('insufficient_funds');
    }
});
