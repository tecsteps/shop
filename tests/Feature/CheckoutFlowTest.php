<?php

use App\Enums\PaymentMethod;
use App\Exceptions\PaymentDeclinedException;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSetting;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Livewire\Livewire;

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

it('calculates weight and price shipping tiers and excludes rates outside their ranges', function () {
    $store = shopStore();
    $product = shopProduct($store, price: 2000);
    $product['variant']->update(['weight_g' => 600]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 2);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries' => ['DE']]);
    $weightRate = ShippingRate::factory()->create([
        'shipping_zone_id' => $zone->id,
        'type' => 'weight',
        'config_json' => ['tiers' => [
            ['min_weight_g' => 0, 'max_weight_g' => 999, 'price_amount' => 600],
            ['min_weight_g' => 1000, 'price_amount' => 900],
        ]],
    ]);
    $priceRate = ShippingRate::factory()->create([
        'shipping_zone_id' => $zone->id,
        'type' => 'price',
        'config_json' => ['tiers' => [
            ['min_order_amount' => 0, 'max_order_amount' => 3999, 'price_amount' => 700],
            ['min_order_amount' => 4000, 'price_amount' => 0],
        ]],
    ]);
    $unavailableRate = ShippingRate::factory()->create([
        'shipping_zone_id' => $zone->id,
        'type' => 'weight',
        'config_json' => ['tiers' => [['min_weight_g' => 2000, 'price_amount' => 1200]]],
    ]);
    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->start($cart);
    $checkout = $checkouts->setAddress($checkout, 'buyer@example.test', [
        'first_name' => 'Alex', 'last_name' => 'Buyer', 'address1' => '1 Test Street',
        'city' => 'Berlin', 'country' => 'DE', 'country_code' => 'DE', 'postal_code' => '10115',
    ]);

    $rates = $checkouts->availableShippingRates($checkout)->keyBy('id');
    expect($rates->keys()->all())->toContain($weightRate->id, $priceRate->id)
        ->and($rates->keys()->all())->not->toContain($unavailableRate->id)
        ->and($rates[$weightRate->id]->price_amount)->toBe(900)
        ->and($rates[$priceRate->id]->price_amount)->toBe(0);

    $checkout = $checkouts->selectShippingMethod($checkout, $weightRate->id);
    expect($checkout->shipping_amount)->toBe(900);
});

it('uses the most specific shipping zone and weighs only physical items', function () {
    $store = shopStore();
    $physical = shopProduct($store, price: 1500);
    $physical['variant']->update(['weight_g' => 750]);
    $digital = shopProduct($store, price: 500, requiresShipping: false);
    $digital['variant']->update(['weight_g' => 10000]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $physical['variant'], 1);
    app(CartService::class)->add($cart, $digital['variant'], 3);
    $countryZone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries' => ['DE'], 'regions' => []]);
    $regionZone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries' => ['DE'], 'regions' => ['DE-BE']]);
    ShippingRate::factory()->create([
        'shipping_zone_id' => $countryZone->id,
        'config_json' => ['price_amount' => 300],
        'price_amount' => 300,
    ]);
    $regionalRate = ShippingRate::factory()->create([
        'shipping_zone_id' => $regionZone->id,
        'config_json' => ['price_amount' => 800],
        'price_amount' => 800,
    ]);
    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->start($cart);
    $checkout = $checkouts->setAddress($checkout, 'buyer@example.test', [
        'first_name' => 'Alex', 'last_name' => 'Buyer', 'address1' => '1 Test Street',
        'city' => 'Berlin', 'province_code' => 'BE', 'country' => 'DE', 'country_code' => 'DE', 'postal_code' => '10115',
    ]);

    $rates = $checkouts->availableShippingRates($checkout);
    expect($rates->modelKeys())->toBe([$regionalRate->id])
        ->and($rates->first()->price_amount)->toBe(800);
    $checkout = $checkouts->selectShippingMethod($checkout, $regionalRate->id);
    expect($checkout->shipping_amount)->toBe(800);
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

it('renders each checkout step from the Livewire component state', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $product = shopProduct($store);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 1);
    session()->put('cart_id', $cart->id);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries' => ['DE']]);
    $rate = ShippingRate::factory()->create(['shipping_zone_id' => $zone->id]);

    $checkout = Livewire::test(CheckoutShow::class)
        ->set('email', 'buyer@example.test')
        ->call('continueToAddress')
        ->assertSet('stepOverride', 2)
        ->assertSee('First name');

    $checkout->set('shipping', [
        'first_name' => 'Alex',
        'last_name' => 'Buyer',
        'address_line_1' => '1 Test Street',
        'address_line_2' => '',
        'city' => 'Berlin',
        'state' => '',
        'country' => 'DE',
        'postal_code' => '10115',
        'phone' => '',
    ])->call('continueToShippingMethod')
        ->assertSee('Choose a shipping method')
        ->assertSee('Alex Buyer')
        ->assertSee('1 Test Street')
        ->set('shippingRateId', $rate->id)
        ->call('continueToPayment')
        ->assertSee('Select a payment method')
        ->assertSee('Card number');
});
