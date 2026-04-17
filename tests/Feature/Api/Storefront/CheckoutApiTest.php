<?php

use App\Enums\StoreDomainType;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\TaxSettings;
use App\Services\CartService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedCheckoutFixture(): array
{
    $store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 1500,
        'requires_shipping' => 1,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 10,
    ]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, (int) $variant->getKey(), 1);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->getKey(),
        'config_json' => ['amount' => 500],
    ]);
    TaxSettings::factory()->create([
        'store_id' => $store->getKey(),
        'config_json' => ['default_rate_bps' => 0],
    ]);

    return [$store, $cart, $rate];
}

it('starts a checkout from an existing cart', function () {
    [, $cart] = seedCheckoutFixture();

    $response = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart->getKey(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'started');
});

it('walks through address + shipping + pay', function () {
    [, $cart, $rate] = seedCheckoutFixture();

    $checkoutId = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart->getKey(),
    ])->json('data.id');

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/address", [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'A',
            'last_name' => 'B',
            'address1' => '1 Main',
            'city' => 'Somewhere',
            'country_code' => 'US',
            'postal_code' => '12345',
        ],
    ])->assertOk()->assertJsonPath('data.status', 'addressed');

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", [
        'shipping_rate_id' => $rate->getKey(),
    ])->assertOk()->assertJsonPath('data.status', 'shipping_selected');

    $payResponse = $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
    ]);

    $payResponse->assertCreated()
        ->assertJsonPath('data.financial_status', 'paid')
        ->assertJsonPath('data.status', 'paid');
});

it('rejects checkout address validation errors', function () {
    [, $cart] = seedCheckoutFixture();

    $checkoutId = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart->getKey(),
    ])->json('data.id');

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/address", [
        'email' => 'not-an-email',
        'shipping_address' => [],
    ])->assertStatus(422)->assertJsonValidationErrors([
        'email',
        'shipping_address.first_name',
    ]);
});

it('returns declined error when card is the decline magic number', function () {
    [, $cart, $rate] = seedCheckoutFixture();

    $checkoutId = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart->getKey(),
    ])->json('data.id');

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/address", [
        'email' => 'b@example.test',
        'shipping_address' => [
            'first_name' => 'A',
            'last_name' => 'B',
            'address1' => '1 Main',
            'city' => 'Somewhere',
            'country_code' => 'US',
            'postal_code' => '12345',
        ],
    ]);
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", [
        'shipping_rate_id' => $rate->getKey(),
    ]);

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkoutId}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
    ])->assertStatus(422)->assertJson(['error' => 'card_declined']);
});
