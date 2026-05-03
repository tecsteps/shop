<?php

use App\Models\Cart;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function storefrontApiCartStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function storefrontApiCartVariant(Store $store): ProductVariant
{
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'classic-cotton-t-shirt')
        ->firstOrFail();

    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->oldest('position')
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 20,
            'quantity_reserved' => 0,
        ]);

    return $variant->refresh();
}

test('storefront cart api creates and retrieves carts for the resolved store', function (): void {
    $store = storefrontApiCartStore();

    $response = $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/carts', ['currency' => $store->default_currency]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.store_id', $store->getKey())
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.cart_version', 1)
        ->assertJsonPath('data.line_count', 0)
        ->assertJsonPath('data.totals.total', 0);

    $this->withHeader('Host', 'shop.test')
        ->getJson("/api/storefront/v1/carts/{$response['data']['id']}")
        ->assertOk()
        ->assertJsonPath('data.id', $response['data']['id'])
        ->assertJsonPath('data.lines', []);
});

test('storefront cart api adds updates and removes line items with version increments', function (): void {
    $store = storefrontApiCartStore();
    $variant = storefrontApiCartVariant($store);

    $cartId = $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/carts')
        ->assertCreated()['data']['id'];

    $addResponse = $this->withHeader('Host', 'shop.test')
        ->postJson("/api/storefront/v1/carts/{$cartId}/lines", [
            'variant_id' => $variant->getKey(),
            'quantity' => 2,
        ]);

    $addResponse
        ->assertCreated()
        ->assertJsonPath('data.cart_version', 2)
        ->assertJsonPath('data.line_count', 2)
        ->assertJsonPath('data.lines.0.quantity', 2)
        ->assertJsonPath('data.lines.0.line_total_amount', 4998);

    $lineId = $addResponse['data']['lines'][0]['id'];

    $this->withHeader('Host', 'shop.test')
        ->putJson("/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
            'quantity' => 3,
            'cart_version' => 2,
        ])
        ->assertOk()
        ->assertJsonPath('data.cart_version', 3)
        ->assertJsonPath('data.lines.0.quantity', 3)
        ->assertJsonPath('data.totals.total', 7497);

    $this->withHeader('Host', 'shop.test')
        ->deleteJson("/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
            'cart_version' => 3,
        ])
        ->assertOk()
        ->assertJsonPath('data.cart_version', 4)
        ->assertJsonPath('data.line_count', 0)
        ->assertJsonPath('data.lines', []);

    expect(Cart::withoutGlobalScopes()->findOrFail($cartId)->cart_version)->toBe(4);
});

test('storefront cart api returns the current cart on version conflicts', function (): void {
    $store = storefrontApiCartStore();
    $variant = storefrontApiCartVariant($store);

    $cartId = $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/carts')
        ->assertCreated()['data']['id'];

    $lineId = $this->withHeader('Host', 'shop.test')
        ->postJson("/api/storefront/v1/carts/{$cartId}/lines", [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
        ])
        ->assertCreated()['data']['lines'][0]['id'];

    $this->withHeader('Host', 'shop.test')
        ->putJson("/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
            'quantity' => 2,
            'cart_version' => 1,
        ])
        ->assertConflict()
        ->assertJsonPath('expected_cart_version', 1)
        ->assertJsonPath('current_cart_version', 2)
        ->assertJsonPath('cart.cart_version', 2)
        ->assertJsonPath('cart.lines.0.quantity', 1);
});

test('storefront cart api validates tenant and request boundaries', function (): void {
    $store = storefrontApiCartStore();

    $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/carts', ['currency' => 'USD'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency');

    $this->withHeader('Host', 'shop.test')
        ->getJson('/api/storefront/v1/carts/999999')
        ->assertNotFound();

    $otherStoreCart = Cart::factory()->create(['store_id' => Store::query()->whereKeyNot($store->getKey())->firstOrFail()->getKey()]);

    $this->withHeader('Host', 'shop.test')
        ->getJson("/api/storefront/v1/carts/{$otherStoreCart->getKey()}")
        ->assertNotFound();
});
