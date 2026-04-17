<?php

use App\Enums\ProductStatus;
use App\Models\Product;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->user = $context['user'];
});

it('lists products for a store', function () {
    Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/products');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters products by status', function () {
    Product::factory()->active()->count(2)->create(['store_id' => $this->store->id]);
    Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Draft]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/products?status=active');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('searches products by title', function () {
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Blue T-Shirt']);
    Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Red Hoodie']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/products?query=T-Shirt');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Blue T-Shirt');
});

it('shows a single product with relations', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/products/'.$product->id);

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.title', $product->title)
        ->assertJsonStructure(['data' => ['options', 'variants', 'media', 'collections']]);
});

it('creates a product', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/v1/stores/'.$this->store->id.'/products', [
            'title' => 'New Product',
            'vendor' => 'Test Vendor',
            'status' => 'draft',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.title', 'New Product')
        ->assertJsonPath('data.vendor', 'Test Vendor')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.handle', 'new-product');
});

it('updates a product', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    $response = $this->actingAs($this->user)
        ->putJson('/api/admin/v1/stores/'.$this->store->id.'/products/'.$product->id, [
            'title' => 'Updated Title',
            'status' => 'active',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.status', 'active');
});

it('archives a product on delete', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson('/api/admin/v1/stores/'.$this->store->id.'/products/'.$product->id);

    $response->assertOk()
        ->assertJsonPath('data.status', 'archived');

    expect($product->fresh()->status)->toBe(ProductStatus::Archived);
});

it('rejects unauthenticated access', function () {
    $response = $this->getJson('/api/admin/v1/stores/'.$this->store->id.'/products');

    $response->assertUnauthorized();
});

it('rejects access from user without store membership', function () {
    $otherUser = \App\Models\User::factory()->create();

    $response = $this->actingAs($otherUser)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/products');

    $response->assertForbidden();
});

it('returns 404 for product from another store', function () {
    $otherStore = \App\Models\Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $otherStore->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/admin/v1/stores/'.$this->store->id.'/products/'.$product->id);

    $response->assertNotFound();
});

it('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/admin/v1/stores/'.$this->store->id.'/products', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('title');
});
