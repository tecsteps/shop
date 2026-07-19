<?php

use App\Enums\CheckoutStatus;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;

/**
 * Build the absolute storefront API URL for the store's primary domain.
 */
function checkoutFlowApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Store with variant (2500, shippable), DE zone with flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function checkoutFlowSetup(): array
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

function deAddress(): array
{
    return [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => 'DE',
        'country_code' => 'DE',
        'postal_code' => '10115',
    ];
}

test('creates a checkout from a cart', function () {
    [$store, $variant] = checkoutFlowSetup();

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);
    app(CartService::class)->addLine($cart->refresh(), $variant->id, 1);

    $checkout = app(CheckoutService::class)->createFromCart($cart->refresh(), 'customer@example.com');

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($cart->id)
        ->and($checkout->email)->toBe('customer@example.com')
        ->and($checkout->expires_at)->not->toBeNull()
        ->and($checkout->totals_json['subtotal'])->toBe(7500);
});

test('walks the full checkout flow to payment selected', function () {
    [$store, $variant, $rate] = checkoutFlowSetup();

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    $response = $this->postJson(checkoutFlowApiUrl($store, '/checkouts'), [
        'cart_id' => $cart->id,
        'email' => 'customer@example.com',
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', 'started')
        ->assertJsonPath('email', 'customer@example.com')
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('available_shipping_methods', [])
        ->assertJsonStructure(['id', 'cart_id', 'lines', 'expires_at', 'created_at']);

    $checkoutId = $response->json('id');

    $this->putJson(checkoutFlowApiUrl($store, "/checkouts/{$checkoutId}/address"), [
        'shipping_address' => deAddress(),
        'use_shipping_as_billing' => true,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'addressed')
        ->assertJsonPath('shipping_address_json.city', 'Berlin')
        ->assertJsonPath('billing_address_json.city', 'Berlin')
        ->assertJsonPath('available_shipping_methods.0.id', $rate->id)
        ->assertJsonPath('available_shipping_methods.0.price_amount', 499);

    $this->putJson(checkoutFlowApiUrl($store, "/checkouts/{$checkoutId}/shipping-method"), [
        'shipping_method_id' => $rate->id,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'shipping_selected')
        ->assertJsonPath('totals.shipping', 499);

    $this->putJson(checkoutFlowApiUrl($store, "/checkouts/{$checkoutId}/payment-method"), [
        'payment_method' => 'credit_card',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'payment_selected')
        ->assertJsonPath('payment_method', 'credit_card');

    $checkout = Checkout::findOrFail($checkoutId);

    expect($checkout->expires_at->isFuture())->toBeTrue()
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(2);
});

test('rejects checkout for empty cart', function () {
    [$store] = checkoutFlowSetup();
    $cart = app(CartService::class)->create($store);

    $this->postJson(checkoutFlowApiUrl($store, '/checkouts'), [
        'cart_id' => $cart->id,
        'email' => 'customer@example.com',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['cart_id']);
});

test('expires checkout after timeout and releases reserved inventory', function () {
    [$store, $variant, $rate] = checkoutFlowSetup();
    $service = app(CheckoutService::class);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 3);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, ['shipping_address' => deAddress()]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');

    expect($variant->inventoryItem->refresh()->quantity_reserved)->toBe(3);

    $checkout->update(['expires_at' => now()->subHour()]);

    (new ExpireAbandonedCheckouts)->handle(app(CheckoutService::class));

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});

test('get checkout returns 410 when expired', function () {
    [$store, $variant] = checkoutFlowSetup();

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    $checkout = app(CheckoutService::class)->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout->update(['expires_at' => now()->subHour()]);

    $this->getJson(checkoutFlowApiUrl($store, "/checkouts/{$checkout->id}"))
        ->assertGone();
});
