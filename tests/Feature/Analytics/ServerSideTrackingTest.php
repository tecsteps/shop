<?php

use App\Models\AnalyticsEvent;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\AnalyticsService;
use App\Services\CartService;
use App\Services\CheckoutService;

/**
 * Store with a purchasable variant and a DE shipping zone with flat rate.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function trackingSetup(): array
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
 * Drive a checkout to payment_selected via the full state machine.
 */
function trackingCheckout(Store $store, ProductVariant $variant, ShippingRate $rate): Checkout
{
    $service = app(CheckoutService::class);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

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

    return $service->selectPaymentMethod($checkout, 'credit_card');
}

beforeEach(function () {
    [$this->store, $this->variant, $this->rate] = trackingSetup();
});

test('adding a cart line tracks add_to_cart', function () {
    $cart = app(CartService::class)->create($this->store);
    app(CartService::class)->addLine($cart, $this->variant->id, 2);

    $event = AnalyticsEvent::query()->where('type', 'add_to_cart')->sole();

    expect($event->store_id)->toBe($this->store->id)
        ->and($event->properties_json['product_id'])->toBe($this->variant->product_id)
        ->and($event->properties_json['variant_id'])->toBe($this->variant->id)
        ->and($event->properties_json['quantity'])->toBe(2)
        ->and($event->properties_json['price_amount'])->toBe(2500);
});

test('removing a cart line tracks remove_from_cart', function () {
    $cart = app(CartService::class)->create($this->store);
    $line = app(CartService::class)->addLine($cart, $this->variant->id, 1);

    app(CartService::class)->removeLine($cart->refresh(), $line->id);

    $event = AnalyticsEvent::query()->where('type', 'remove_from_cart')->sole();

    expect($event->properties_json['variant_id'])->toBe($this->variant->id)
        ->and($event->properties_json['quantity'])->toBe(1);
});

test('creating a checkout tracks checkout_started', function () {
    $cart = app(CartService::class)->create($this->store);
    app(CartService::class)->addLine($cart, $this->variant->id, 1);

    $checkout = app(CheckoutService::class)->createFromCart($cart->refresh(), 'customer@example.com');

    $event = AnalyticsEvent::query()->where('type', 'checkout_started')->sole();

    expect($event->store_id)->toBe($this->store->id)
        ->and($event->properties_json['checkout_id'])->toBe($checkout->id)
        ->and($event->properties_json['cart_id'])->toBe($cart->id);
});

test('creating an order tracks checkout_completed with revenue properties', function () {
    $checkout = trackingCheckout($this->store, $this->variant, $this->rate);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $event = AnalyticsEvent::query()->where('type', 'checkout_completed')->sole();

    expect($event->store_id)->toBe($this->store->id)
        ->and($event->properties_json['order_id'])->toBe($order->id)
        ->and($event->properties_json['checkout_id'])->toBe($checkout->id)
        ->and($event->properties_json['total'])->toBe($order->total_amount)
        ->and($event->properties_json['currency'])->toBe($order->currency);
});

test('checkout completed events are idempotent per checkout', function () {
    $checkout = trackingCheckout($this->store, $this->variant, $this->rate);

    $service = app(CheckoutService::class);
    $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);
    $service->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);

    expect(AnalyticsEvent::query()->where('type', 'checkout_completed')->count())->toBe(1);
});

test('analytics failures never break commerce', function () {
    $mock = Mockery::mock(AnalyticsService::class)->makePartial();
    $mock->shouldReceive('track')->andThrow(new RuntimeException('analytics is down'));
    app()->instance(AnalyticsService::class, $mock);

    $cart = app(CartService::class)->create($this->store);
    $line = app(CartService::class)->addLine($cart, $this->variant->id, 2);

    expect($line->quantity)->toBe(2)
        ->and($cart->refresh()->cart_version)->toBe(2);

    $checkout = trackingCheckout($this->store, $this->variant, $this->rate);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->exists)->toBeTrue();
});
