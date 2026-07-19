<?php

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
function shippingApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Store with a shippable variant (2500, 250g each), DE zone + flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate, 3: App\Models\Checkout}
 */
function shippingSetup(array $variantAttributes = []): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(50)->create(array_merge([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'weight_g' => 250,
    ], $variantAttributes));

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 3);

    $checkout = app(CheckoutService::class)->createFromCart($cart->refresh(), 'jane@example.com');

    return [$store, $variant, $rate, $checkout];
}

function shippingAddressFor(string $country): array
{
    return [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => $country,
        'country_code' => $country,
        'postal_code' => '10115',
    ];
}

test('returns available shipping rates for address', function () {
    [$store, , $rate, $checkout] = shippingSetup();

    $response = $this->putJson(shippingApiUrl($store, "/checkouts/{$checkout->id}/address"), [
        'shipping_address' => shippingAddressFor('DE'),
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'addressed')
        ->assertJsonPath('available_shipping_methods.0.id', $rate->id)
        ->assertJsonPath('available_shipping_methods.0.name', 'Standard Shipping')
        ->assertJsonPath('available_shipping_methods.0.type', 'flat')
        ->assertJsonPath('available_shipping_methods.0.price_amount', 499)
        ->assertJsonStructure(['available_shipping_methods' => [['currency', 'estimated_days_min', 'estimated_days_max']]]);
});

test('returns empty when no zone matches address', function () {
    [$store, , , $checkout] = shippingSetup();

    $this->putJson(shippingApiUrl($store, "/checkouts/{$checkout->id}/address"), [
        'shipping_address' => shippingAddressFor('FR'),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shipping_address']);
});

test('calculates flat rate correctly', function () {
    [$store, , $rate, $checkout] = shippingSetup();
    $service = app(CheckoutService::class);

    $checkout = $service->setAddress($checkout, ['shipping_address' => shippingAddressFor('DE')]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    expect($checkout->totals_json['shipping'])->toBe(499);
});

test('calculates weight based rate correctly', function () {
    [$store, , , $checkout] = shippingSetup();
    $service = app(CheckoutService::class);

    $zone = ShippingZone::where('store_id', $store->id)->sole();
    $weightRate = ShippingRate::factory()->weight([
        ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
        ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
    ])->create(['zone_id' => $zone->id]);

    // 3 x 250g = 750g -> second range.
    $checkout = $service->setAddress($checkout, ['shipping_address' => shippingAddressFor('DE')]);
    $checkout = $service->setShippingMethod($checkout, $weightRate->id);

    expect($checkout->totals_json['shipping'])->toBe(899);
});

test('returns zero shipping when all items are digital', function () {
    [$store, , , $checkout] = shippingSetup(['requires_shipping' => false]);
    $service = app(CheckoutService::class);

    $checkout = $service->setAddress($checkout, ['shipping_address' => shippingAddressFor('DE')]);
    $checkout = $service->setShippingMethod($checkout, null);

    expect($checkout->totals_json['shipping'])->toBe(0)
        ->and($checkout->shipping_method_id)->toBeNull()
        ->and($checkout->status->value)->toBe('shipping_selected');
});
