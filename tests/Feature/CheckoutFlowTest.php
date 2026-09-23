<?php

use App\Enums\PaymentMethod;
use App\Exceptions\PaymentDeclinedException;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSetting;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;

it('releases reservations after a declined card and can complete a successful retry once', function () {
    $store = shopStore();
    $product = shopProduct($store, stock: 5);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 2);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id]);
    $rate = ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'price_amount' => 500]);
    TaxSetting::factory()->create(['store_id' => $store->id, 'default_rate' => 0, 'rates_json' => []]);
    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->start($cart);
    $checkout = $checkouts->setAddress($checkout, 'buyer@example.test', [
        'first_name' => 'Alex', 'last_name' => 'Buyer', 'address1' => '1 Test Street',
        'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115',
    ]);
    $checkout = $checkouts->selectShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    expect($product['inventory']->refresh()->quantity_reserved)->toBe(2);
    expect(fn () => $checkouts->pay($checkout, ['card_number' => '4000000000000002']))
        ->toThrow(PaymentDeclinedException::class);
    expect($product['inventory']->refresh()->quantity_reserved)->toBe(0)
        ->and($checkout->refresh()->status)->toBe('shipping_selected');

    $checkout = $checkouts->selectPaymentMethod($checkout->refresh(), PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, ['card_number' => '4242424242424242']);
    $duplicateAttempt = $checkouts->pay($checkout->refresh(), ['card_number' => '4242424242424242']);

    expect($order->id)->toBe($duplicateAttempt->id)
        ->and($order->status)->toBe('paid')
        ->and($order->total_amount)->toBe(5500)
        ->and($product['inventory']->refresh()->quantity_on_hand)->toBe(3)
        ->and($product['inventory']->refresh()->quantity_reserved)->toBe(0)
        ->and($cart->refresh()->status)->toBe('converted');
});

it('holds inventory for a bank transfer until it is confirmed', function () {
    $store = shopStore();
    $product = shopProduct($store, price: 3200, stock: 4);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 1);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id]);
    $rate = ShippingRate::factory()->create(['shipping_zone_id' => $zone->id]);
    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->start($cart);
    $checkout = $checkouts->setAddress($checkout, 'buyer@example.test', [
        'first_name' => 'Alex', 'last_name' => 'Buyer', 'address1' => '1 Test Street',
        'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $checkouts->selectShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::BankTransfer);
    $order = $checkouts->pay($checkout);

    expect($order->financial_status)->toBe('pending')
        ->and($product['inventory']->refresh()->quantity_on_hand)->toBe(4)
        ->and($product['inventory']->refresh()->quantity_reserved)->toBe(1);

    $confirmedOrder = app(OrderService::class)->confirmBankTransfer($order);

    expect($confirmedOrder->financial_status)->toBe('paid')
        ->and($product['inventory']->refresh()->quantity_on_hand)->toBe(3)
        ->and($product['inventory']->refresh()->quantity_reserved)->toBe(0);
});

it('skips shipping for a digital product and automatically fulfills it after payment', function () {
    $store = shopStore();
    $product = shopProduct($store, price: 1500, stock: 3, requiresShipping: false);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 1);
    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->start($cart);
    $checkout = $checkouts->setAddress($checkout, 'buyer@example.test', []);
    $checkout = $checkouts->selectShippingMethod($checkout);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::PayPal);
    $order = $checkouts->pay($checkout);

    expect($checkout->refresh()->shipping_method_id)->toBeNull()
        ->and($order->fulfillment_status)->toBe('fulfilled')
        ->and($order->fulfillments)->toHaveCount(1)
        ->and($product['inventory']->refresh()->quantity_on_hand)->toBe(2);
});

it('expires a checkout and releases its reserved inventory exactly once', function () {
    $store = shopStore();
    $product = shopProduct($store, stock: 3);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 2);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id]);
    $rate = ShippingRate::factory()->create(['shipping_zone_id' => $zone->id]);
    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->start($cart);
    $checkout = $checkouts->setAddress($checkout, 'buyer@example.test', [
        'first_name' => 'Alex', 'last_name' => 'Buyer', 'address1' => '1 Test Street',
        'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $checkouts->selectShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    $checkouts->expire($checkout);
    $checkouts->expire($checkout->refresh());

    expect($checkout->refresh()->status)->toBe('expired')
        ->and($product['inventory']->refresh()->quantity_reserved)->toBe(0);
});
