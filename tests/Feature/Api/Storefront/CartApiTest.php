<?php

use App\Enums\StoreDomainType;
use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedApiStore(string $hostname = 'shop.test'): Store
{
    $store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => $hostname,
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    return $store;
}

function seedProductWithVariant(Store $store, int $price = 1000, int $onHand = 10): ProductVariant
{
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => $price,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => $onHand,
    ]);

    return $variant;
}

it('creates a cart via the API', function () {
    seedApiStore();

    $response = $this->postJson('http://shop.test/api/storefront/v1/carts');

    $response->assertCreated()
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.cart_version', 1);
});

it('adds a line to an existing cart', function () {
    $store = seedApiStore();
    $variant = seedProductWithVariant($store);

    $create = $this->postJson('http://shop.test/api/storefront/v1/carts');
    $cartId = $create->json('data.id');

    $response = $this->postJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines", [
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.cart_version', 2);

    expect(Cart::query()->findOrFail($cartId)->lines()->count())->toBe(1);
});

it('rejects missing variant_id with 422', function () {
    seedApiStore();
    $create = $this->postJson('http://shop.test/api/storefront/v1/carts');
    $cartId = $create->json('data.id');

    $response = $this->postJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines", [
        'quantity' => 1,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['variant_id']);
});

it('returns 404 for a cart belonging to a different store', function () {
    $storeA = seedApiStore('shop.test');
    $storeB = seedApiStore('other.test');

    $cart = Cart::factory()->create(['store_id' => $storeB->getKey()]);

    $response = $this->getJson("http://shop.test/api/storefront/v1/carts/{$cart->getKey()}");

    $response->assertNotFound();
});
