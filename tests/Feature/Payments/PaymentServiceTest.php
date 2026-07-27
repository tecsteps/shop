<?php

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Support\Facades\DB;

/**
 * Store with variant (2500, shippable), DE zone with flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function paymentServiceSetup(): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    return [$store, $variant, $rate];
}

/**
 * Drive a checkout to payment_selected with the given method.
 */
function paymentServiceCheckout(Store $store, ProductVariant $variant, ShippingRate $rate, string $method, int $quantity = 2): Checkout
{
    $service = app(CheckoutService::class);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $quantity);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    return $service->selectPaymentMethod($checkout, $method);
}

test('resolves MockPaymentProvider from container', function () {
    expect(app(PaymentProvider::class))->toBeInstanceOf(MockPaymentProvider::class);
});

test('processes credit card payment and creates order as paid', function () {
    [$store, $variant, $rate] = paymentServiceSetup();
    $checkout = paymentServiceCheckout($store, $variant, $rate, 'credit_card');

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->financial_status)->toBe(FinancialStatus::Paid);

    $item = $variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(8)
        ->and($item->quantity_reserved)->toBe(0);

    $payment = $order->payments->first();
    expect($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->provider_payment_id)->toStartWith('mock_');
});

test('processes PayPal payment and creates order as paid', function () {
    [$store, $variant, $rate] = paymentServiceSetup();
    $checkout = paymentServiceCheckout($store, $variant, $rate, 'paypal');

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['payment_method' => 'paypal']);

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
        ->and($order->payments->first()->method)->toBe(PaymentMethod::Paypal);
});

test('processes bank transfer and creates order as pending', function () {
    [$store, $variant, $rate] = paymentServiceSetup();
    $checkout = paymentServiceCheckout($store, $variant, $rate, 'bank_transfer');

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['payment_method' => 'bank_transfer']);

    expect($order->financial_status)->toBe(FinancialStatus::Pending);

    $item = $variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(10)
        ->and($item->quantity_reserved)->toBe(2);

    expect($order->payments->first()->status)->toBe(PaymentStatus::Pending);
});

test('creates a payment record with correct method and provider', function () {
    [$store, $variant, $rate] = paymentServiceSetup();
    $checkout = paymentServiceCheckout($store, $variant, $rate, 'credit_card');

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $payment = $order->payments->first();

    expect($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->provider)->toBe('mock')
        ->and($payment->amount)->toBe($order->total_amount)
        ->and($payment->currency)->toBe($order->currency);
});

test('stores the provider response encrypted at rest', function () {
    [$store, $variant, $rate] = paymentServiceSetup();
    $checkout = paymentServiceCheckout($store, $variant, $rate, 'credit_card');

    $order = app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => '4242 4242 4242 4242',
        'card_holder' => 'Jane Doe',
    ]);

    $raw = DB::table('payments')->where('order_id', $order->id)->value('raw_json_encrypted');

    expect($raw)->not->toBeNull()
        ->not->toContain('card_last4')
        ->not->toContain('4242');

    $payload = $order->payments->first()->raw_json_encrypted;

    expect($payload['card_last4'])->toBe('4242')
        ->and($payload['card_holder'])->toBe('Jane Doe')
        ->and($payload['status'])->toBe('captured');
});
