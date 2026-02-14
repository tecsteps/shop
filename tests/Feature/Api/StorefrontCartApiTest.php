<?php

use App\Enums\InventoryPolicy;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-14 12:00:00'));
    Cache::flush();
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function storefrontCartApiUrl(string $hostname, string $path): string
{
    return 'http://'.$hostname.$path;
}

function storefrontCartApiCreateStore(string $hostname = 'cart-api.test'): Store
{
    $organization = Organization::query()->create([
        'name' => 'Cart API Org',
        'billing_email' => 'billing+cart-api@example.test',
    ]);

    $store = Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Cart API Store',
        'handle' => 'cart-api-store',
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);

    StoreDomain::query()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
        'type' => 'storefront',
        'is_primary' => true,
        'tls_mode' => 'managed',
        'created_at' => now(),
    ]);

    return $store;
}

function storefrontCartApiCreateVariant(
    Store $store,
    string $suffix,
    int $price = 2500,
    int $quantityOnHand = 10,
    InventoryPolicy $policy = InventoryPolicy::Deny,
): ProductVariant {
    $product = Product::query()->create([
        'store_id' => $store->id,
        'title' => 'Cart API Product '.$suffix,
        'handle' => 'cart-api-product-'.$suffix,
        'status' => 'active',
        'description_html' => null,
        'vendor' => null,
        'product_type' => null,
        'tags' => [],
        'published_at' => now(),
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SKU-CART-API-'.$suffix,
        'barcode' => null,
        'price_amount' => $price,
        'compare_at_amount' => null,
        'currency' => 'EUR',
        'weight_g' => 150,
        'requires_shipping' => true,
        'is_default' => true,
        'position' => 1,
        'status' => 'active',
    ]);

    InventoryItem::query()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $quantityOnHand,
        'quantity_reserved' => 0,
        'policy' => $policy,
    ]);

    return $variant;
}

test('supports the storefront cart flow create add update and remove', function (): void {
    $hostname = 'cart-flow.test';
    $store = storefrontCartApiCreateStore($hostname);
    $variant = storefrontCartApiCreateVariant($store, 'flow', price: 2500);

    $createResponse = $this->postJson(
        storefrontCartApiUrl($hostname, '/api/storefront/v1/carts'),
        ['currency' => 'EUR'],
    );

    $createResponse->assertCreated()
        ->assertJsonPath('currency', 'EUR')
        ->assertJsonPath('cart_version', 1)
        ->assertJsonPath('status', 'active');

    $cartId = (int) $createResponse->json('id');

    $addResponse = $this->postJson(
        storefrontCartApiUrl($hostname, "/api/storefront/v1/carts/{$cartId}/lines"),
        [
            'variant_id' => $variant->id,
            'quantity' => 2,
            'cart_version' => 1,
        ],
    );

    $addResponse->assertCreated()
        ->assertJsonPath('lines.0.variant_id', $variant->id)
        ->assertJsonPath('lines.0.quantity', 2);

    $lineId = (int) $addResponse->json('lines.0.id');
    $cartVersion = (int) $addResponse->json('cart_version');

    $updateResponse = $this->putJson(
        storefrontCartApiUrl($hostname, "/api/storefront/v1/carts/{$cartId}/lines/{$lineId}"),
        [
            'quantity' => 3,
            'cart_version' => $cartVersion,
        ],
    );

    $updateResponse->assertOk()
        ->assertJsonPath('lines.0.quantity', 3);

    $updatedVersion = (int) $updateResponse->json('cart_version');

    $this->deleteJson(
        storefrontCartApiUrl($hostname, "/api/storefront/v1/carts/{$cartId}/lines/{$lineId}"),
        ['cart_version' => $updatedVersion],
    )->assertOk()
        ->assertJsonCount(0, 'lines');
});

test('returns 404 for unknown cart ids', function (): void {
    $hostname = 'cart-unknown.test';
    storefrontCartApiCreateStore($hostname);

    $this->getJson(
        storefrontCartApiUrl($hostname, '/api/storefront/v1/carts/999999'),
    )->assertNotFound();
});

test('returns 409 when cart version does not match current version', function (): void {
    $hostname = 'cart-version-mismatch.test';
    $store = storefrontCartApiCreateStore($hostname);
    $variant = storefrontCartApiCreateVariant($store, 'version', price: 1200);

    $cart = Cart::query()->create([
        'store_id' => $store->id,
        'customer_id' => null,
        'currency' => 'EUR',
        'cart_version' => 3,
        'status' => 'active',
    ]);

    $line = CartLine::query()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1200,
        'line_subtotal_amount' => 1200,
        'line_discount_amount' => 0,
        'line_total_amount' => 1200,
    ]);

    $this->putJson(
        storefrontCartApiUrl($hostname, "/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}"),
        [
            'quantity' => 2,
            'cart_version' => 2,
        ],
    )->assertStatus(409);
});

test('returns 422 when requested quantity exceeds deny policy inventory', function (): void {
    $hostname = 'cart-inventory.test';
    $store = storefrontCartApiCreateStore($hostname);
    $variant = storefrontCartApiCreateVariant(
        store: $store,
        suffix: 'inventory',
        price: 990,
        quantityOnHand: 1,
        policy: InventoryPolicy::Deny,
    );

    $cart = Cart::query()->create([
        'store_id' => $store->id,
        'customer_id' => null,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    $this->postJson(
        storefrontCartApiUrl($hostname, "/api/storefront/v1/carts/{$cart->id}/lines"),
        [
            'variant_id' => $variant->id,
            'quantity' => 2,
            'cart_version' => 1,
        ],
    )->assertUnprocessable();
});
