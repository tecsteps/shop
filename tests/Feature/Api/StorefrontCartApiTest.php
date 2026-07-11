<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->forgetInstance('current_store');
    $this->store = Store::factory()->create();
    StoreDomain::factory()->for($this->store)->create(['hostname' => 'acme-fashion.test']);
    $product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    $this->variant->inventoryItem->update(['quantity_on_hand' => 10]);
    $this->withServerVariables(['HTTP_HOST' => 'acme-fashion.test']);
});

it('creates retrieves and mutates a storefront cart', function () {
    $create = $this->postJson('/api/storefront/v1/carts')->assertCreated()->assertJsonPath('data.version', 1);
    $cartId = $create->json('data.id');

    $this->getJson("/api/storefront/v1/carts/{$cartId}")->assertSuccessful()->assertJsonPath('data.lines', []);

    $this->postJson("/api/storefront/v1/carts/{$cartId}/lines", ['variant_id' => $this->variant->id, 'quantity' => 2, 'expected_version' => 1])
        ->assertSuccessful()
        ->assertJsonPath('data.version', 2)
        ->assertJsonPath('data.lines.0.total_amount', 5000);
});

it('returns a conflict with current cart state for stale versions', function () {
    $cartId = $this->postJson('/api/storefront/v1/carts')->json('data.id');
    $this->postJson("/api/storefront/v1/carts/{$cartId}/lines", ['variant_id' => $this->variant->id, 'quantity' => 1, 'expected_version' => 1])->assertSuccessful();

    $this->putJson("/api/storefront/v1/carts/{$cartId}/lines/1", ['quantity' => 2, 'expected_version' => 1])
        ->assertConflict()
        ->assertJsonPath('code', 'cart_version_conflict')
        ->assertJsonPath('cart.version', 2);
});

it('rejects cross-store cart access', function () {
    $otherStore = Store::factory()->create();
    app()->instance('current_store', $otherStore);
    $otherCart = \App\Models\Cart::factory()->for($otherStore)->create();
    app()->forgetInstance('current_store');

    $this->getJson("/api/storefront/v1/carts/{$otherCart->id}")->assertNotFound();
});
