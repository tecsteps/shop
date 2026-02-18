<?php

use App\Models\Product;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists products with authentication', function () {
    Product::factory()->active()->count(3)->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

    expect($response->json('meta.total'))->toBe(3);
});

it('creates a product via API', function () {
    $token = $this->user->createToken('test', ['write-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
            'title' => 'New Test Product',
            'description' => 'A test product description',
        ]);

    $response->assertStatus(201);
    expect($response->json('data.title'))->toBe('New Test Product');
});

it('updates a product via API', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['write-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->putJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}", [
            'title' => 'Updated Title',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.title'))->toBe('Updated Title');
});

it('deletes a draft product via API', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => 'draft',
    ]);

    $token = $this->user->createToken('test', ['write-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->deleteJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}");

    $response->assertStatus(200);
});

it('requires write-products ability for mutations', function () {
    $token = $this->user->createToken('test', ['read-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
            'title' => 'New Product',
        ]);

    $response->assertStatus(403);
});

it('returns 401 without token', function () {
    $response = $this->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertStatus(401);
});

it('paginates results', function () {
    Product::factory()->active()->count(25)->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-products'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertStatus(200);
    expect($response->json('meta.total'))->toBe(25);
    expect(count($response->json('data')))->toBe(15);
});
