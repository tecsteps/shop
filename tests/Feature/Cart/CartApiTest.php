<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->bindStoreToAppHost($this->store);
    $this->service = app(CartService::class);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $this->variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 50,
        'policy' => InventoryPolicy::Deny,
    ]);
});

it('creates a cart via the API', function (): void {
    $response = $this->postJson('/api/storefront/v1/carts', ['currency' => 'EUR']);

    $response->assertCreated()
        ->assertJsonPath('currency', 'EUR')
        ->assertJsonPath('cart_version', 1)
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('totals.subtotal', 0);
});

it('retrieves an existing cart with totals', function (): void {
    $cart = $this->service->create($this->store);
    $this->service->addLine($cart, $this->variant->id, 2);

    $response = $this->getJson("/api/storefront/v1/carts/{$cart->id}");

    $response->assertOk()
        ->assertJsonPath('id', $cart->id)
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('totals.item_count', 2);
});

it('adds a line via the API', function (): void {
    $cart = $this->service->create($this->store);

    $response = $this->postJson("/api/storefront/v1/carts/{$cart->id}/lines", [
        'variant_id' => $this->variant->id,
        'quantity' => 3,
    ]);

    $response->assertOk()
        ->assertJsonPath('totals.item_count', 3)
        ->assertJsonPath('cart_version', 2);
});

it('updates a cart line with matching version', function (): void {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    $response = $this->putJson("/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 4,
        'cart_version' => $cart->fresh()->cart_version,
    ]);

    $response->assertOk()
        ->assertJsonPath('totals.item_count', 4);
});

it('removes a cart line', function (): void {
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $this->variant->id, 1);

    $response = $this->deleteJson("/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}", [
        'cart_version' => $cart->fresh()->cart_version,
    ]);

    $response->assertOk()
        ->assertJsonPath('totals.item_count', 0);
});

it('returns 404 for a missing cart', function (): void {
    $response = $this->getJson('/api/storefront/v1/carts/99999');

    $response->assertNotFound();
});
