<?php

use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ProductService;

function makeFullCheckout(array $overrides = []): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $product = app(ProductService::class)->create($store, ['title' => 'Widget', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $product->variants()->first()->id, 2);

    $checkout = app(CheckoutService::class)->create($cart, 'customer@example.com');

    return ['checkout' => $checkout, 'cart' => $cart, 'store' => $store, 'product' => $product, 'rate' => $rate];
}

it('creates a checkout from a cart', function () {
    ['checkout' => $checkout] = makeFullCheckout();

    expect($checkout->status)->toBe('started');
    expect($checkout->cart_id)->not->toBeNull();
});

it('rejects checkout for empty cart', function () {
    $ctx = createStoreContext();
    $cart = app(CartService::class)->create($ctx['store']);

    expect(fn () => app(CheckoutService::class)->create($cart, 'x@example.com'))
        ->toThrow(InvalidArgumentException::class);
});

it('completes full checkout happy path', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $product = app(ProductService::class)->create($store, ['title' => 'Widget', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $product->variants()->first()->id, 2);
    $checkout = app(CheckoutService::class)->create($cart, 'customer@example.com');

    app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'customer@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);
    app(CheckoutService::class)->setShippingMethod($checkout, $rate->id);
    app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->financial_status)->toBe('paid');
    expect($checkout->fresh()->status)->toBe('completed');
    expect($cart->fresh()->status)->toBe('converted');
    expect($product->variants()->first()->inventoryItem->fresh()->quantity_on_hand)->toBe(8);
});

it('prevents duplicate orders from same checkout', function () {
    ['checkout' => $checkout] = makeFullCheckout();
    app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'customer@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);
    app(CheckoutService::class)->setShippingMethod($checkout, \App\Models\ShippingRate::first()->id);
    app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');

    $first = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);
    $second = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($first->id)->toBe($second->id);
    expect(Order::where('checkout_id', $checkout->id)->count())->toBe(1);
});
