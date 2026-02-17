<?php

use App\Models\Product;
use App\Models\ProductVariant;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->user = $this->ctx['user'];
});

it('lists products with authentication', function () {
    Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-products']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('creates a product via API', function () {
    $token = $this->user->createToken('test', ['write-products', 'read-products']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
        'title' => 'Test API Product',
        'description_html' => '<p>A test product.</p>',
        'vendor' => 'Test Vendor',
        'price_amount' => 2500,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'Test API Product');
});

it('updates a product via API', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['write-products', 'read-products']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->putJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');
});

it('deletes a draft product via API', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => 'draft',
    ]);
    ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
    ]);

    $token = $this->user->createToken('test', ['write-products', 'read-products']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->deleteJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}");

    $response->assertOk();
});

it('requires write-products ability for mutations', function () {
    $token = $this->user->createToken('test', ['read-products']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
        'title' => 'Unauthorized Product',
    ]);

    $response->assertForbidden();
});

it('returns 401 without token', function () {
    $response = $this->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertUnauthorized();
});

it('paginates results', function () {
    Product::factory()->count(25)->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-products']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson("/api/admin/v1/stores/{$this->store->id}/products?per_page=15");

    $response->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 25);
});
