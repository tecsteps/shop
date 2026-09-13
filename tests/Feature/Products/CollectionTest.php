<?php

use App\Models\Collection;
use App\Models\Product;
use App\Services\ProductService;
use Tests\Support\CatalogTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a collection with a unique handle', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->postJson("/api/admin/v1/stores/{$store->id}/collections", [
        'title' => 'Summer Sale',
        'description_html' => '<p>Best items for summer.</p>',
        'type' => 'manual',
        'status' => 'active',
    ], CatalogTestHelper::adminHeaders($user));

    $response->assertCreated();
    $response->assertJsonPath('data.title', 'Summer Sale');
    $response->assertJsonPath('data.handle', 'summer-sale');
    $response->assertJsonPath('data.type', 'manual');
    expect(Collection::query()->where('store_id', $store->id)->count())->toBe(1);
});

it('dedupes collection handles', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);

    $this->postJson("/api/admin/v1/stores/{$store->id}/collections",
        ['title' => 'Sale', 'type' => 'manual'], $headers)->assertCreated()->assertJsonPath('data.handle', 'sale');
    $this->postJson("/api/admin/v1/stores/{$store->id}/collections",
        ['title' => 'Sale', 'type' => 'manual'], $headers)->assertCreated()->assertJsonPath('data.handle', 'sale-1');

    $this->postJson("/api/admin/v1/stores/{$store->id}/collections",
        ['title' => 'Other', 'handle' => 'sale', 'type' => 'manual'], $headers)
        ->assertUnprocessable()->assertJsonValidationErrors(['handle']);
});

it('validates collection payloads', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);

    $this->postJson("/api/admin/v1/stores/{$store->id}/collections", [], $headers)
        ->assertUnprocessable()->assertJsonValidationErrors(['title', 'type']);

    $this->postJson("/api/admin/v1/stores/{$store->id}/collections",
        ['title' => 'Bad', 'type' => 'magic'], $headers)
        ->assertUnprocessable()->assertJsonValidationErrors(['type']);

    ['store' => $otherStore] = $this->createStoreContext();
    $foreignProduct = app(ProductService::class)->create($otherStore, CatalogTestHelper::productPayload());

    $this->postJson("/api/admin/v1/stores/{$store->id}/collections", [
        'title' => 'Mixed', 'type' => 'manual', 'product_ids' => [$foreignProduct->id],
    ], $headers)->assertUnprocessable()->assertJsonValidationErrors(['product_ids.0']);
});

it('adds products to a collection on create', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $service = app(ProductService::class);

    $ids = [];
    foreach (['One', 'Two', 'Three'] as $index => $title) {
        $payload = CatalogTestHelper::productPayload(['title' => "Product {$title}"]);
        $payload['variants'][0]['sku'] = "SKU-{$index}";
        $ids[] = $service->create($store, $payload)->id;
    }

    $response = $this->postJson("/api/admin/v1/stores/{$store->id}/collections", [
        'title' => 'Bundle', 'type' => 'manual', 'product_ids' => $ids,
    ], CatalogTestHelper::adminHeaders($user));

    $response->assertCreated();
    $response->assertJsonPath('data.products_count', 3);
    expect(Collection::query()->findOrFail($response->json('data.id'))->products()->count())->toBe(3);
});

it('lists collections with product counts and filters', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);
    $service = app(ProductService::class);

    $product = $service->create($store, CatalogTestHelper::productPayload());
    $active = Collection::factory()->create(['store_id' => $store->id, 'title' => 'Active Picks', 'status' => 'active']);
    $active->products()->attach($product->id, ['position' => 0]);
    Collection::factory()->create(['store_id' => $store->id, 'title' => 'Draft Picks', 'status' => 'draft']);

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/collections", $headers);
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    $response->assertJsonPath('data.0.products_count', 1);

    expect($this->getJson("/api/admin/v1/stores/{$store->id}/collections?status=draft", $headers)->json('data'))->toHaveCount(1);
    expect($this->getJson("/api/admin/v1/stores/{$store->id}/collections?query=Active", $headers)->json('data'))->toHaveCount(1);

    $paged = $this->getJson("/api/admin/v1/stores/{$store->id}/collections?per_page=1&page=2", $headers);
    $paged->assertOk();
    $paged->assertJsonPath('meta.total', 2);
    $paged->assertJsonPath('meta.current_page', 2);
});

it('shows a collection with ordered products', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $service = app(ProductService::class);

    $payloadB = CatalogTestHelper::productPayload(['title' => 'Product B']);
    $payloadB['variants'][0]['sku'] = 'SKU-B';
    $productA = $service->create($store, CatalogTestHelper::productPayload(['title' => 'Product A']));
    $productB = $service->create($store, $payloadB);

    $collection = Collection::factory()->create(['store_id' => $store->id]);
    $collection->products()->attach([$productA->id => ['position' => 1], $productB->id => ['position' => 0]]);

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/collections/{$collection->id}", CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    $response->assertJsonPath('data.products.0.id', $productB->id);
    $response->assertJsonPath('data.products.0.position', 0);
    $response->assertJsonPath('data.products.1.id', $productA->id);
});

it('replaces the product set and reorders positions', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $service = app(ProductService::class);

    $ids = [];
    foreach (['A', 'B', 'C'] as $title) {
        $payload = CatalogTestHelper::productPayload(['title' => "Product {$title}"]);
        $payload['variants'][0]['sku'] = "SKU-{$title}";
        $ids[] = $service->create($store, $payload)->id;
    }

    $collection = Collection::factory()->create(['store_id' => $store->id]);
    $collection->products()->attach([$ids[0] => ['position' => 0]]);

    $reordered = [$ids[2], $ids[0], $ids[1]];
    $this->putJson("/api/admin/v1/stores/{$store->id}/collections/{$collection->id}", [
        'product_ids' => $reordered,
    ], CatalogTestHelper::adminHeaders($user))->assertOk();

    $positions = $collection->fresh()->products()->orderByPivot('position')->pluck('products.id')->all();
    expect($positions)->toBe($reordered);
});

it('adds and removes products incrementally', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $service = app(ProductService::class);

    $ids = [];
    foreach (['A', 'B', 'C'] as $title) {
        $payload = CatalogTestHelper::productPayload(['title' => "Product {$title}"]);
        $payload['variants'][0]['sku'] = "SKU-{$title}";
        $ids[] = $service->create($store, $payload)->id;
    }

    $collection = Collection::factory()->create(['store_id' => $store->id]);
    $collection->products()->attach([$ids[0] => ['position' => 0], $ids[1] => ['position' => 1]]);

    $this->putJson("/api/admin/v1/stores/{$store->id}/collections/{$collection->id}", [
        'add_product_ids' => [$ids[2]],
        'remove_product_ids' => [$ids[0]],
    ], CatalogTestHelper::adminHeaders($user))->assertOk();

    expect($collection->fresh()->products()->pluck('products.id')->sort()->values()->all())
        ->toBe(collect([$ids[1], $ids[2]])->sort()->values()->all());
});

it('updates collection fields and transitions status', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'status' => 'draft']);

    $response = $this->putJson("/api/admin/v1/stores/{$store->id}/collections/{$collection->id}", [
        'title' => 'Renamed',
        'status' => 'active',
    ], CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Renamed');
    $response->assertJsonPath('data.status', 'active');
});

it('deletes a collection without deleting products', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $product = app(ProductService::class)->create($store, CatalogTestHelper::productPayload());

    $collection = Collection::factory()->create(['store_id' => $store->id]);
    $collection->products()->attach($product->id);

    $this->deleteJson("/api/admin/v1/stores/{$store->id}/collections/{$collection->id}", [], CatalogTestHelper::adminHeaders($user))
        ->assertOk()->assertJsonPath('message', 'Collection deleted');

    expect(Collection::query()->whereKey($collection->id)->exists())->toBeFalse();
    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});

it('scopes collections to the current store', function () {
    ['store' => $storeA, 'user' => $userA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    $headersA = CatalogTestHelper::adminHeaders($userA);

    Collection::factory()->count(2)->create(['store_id' => $storeA->id]);
    $foreign = Collection::factory()->create(['store_id' => $storeB->id]);

    expect($this->getJson("/api/admin/v1/stores/{$storeA->id}/collections", $headersA)->json('data'))->toHaveCount(2);
    $this->getJson("/api/admin/v1/stores/{$storeA->id}/collections/{$foreign->id}", $headersA)->assertNotFound();
    $this->putJson("/api/admin/v1/stores/{$storeA->id}/collections/{$foreign->id}", ['title' => 'Hijack'], $headersA)->assertNotFound();
    $this->deleteJson("/api/admin/v1/stores/{$storeA->id}/collections/{$foreign->id}", [], $headersA)->assertNotFound();
});

it('enforces authentication and abilities on collection endpoints', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $this->getJson("/api/admin/v1/stores/{$store->id}/collections")->assertUnauthorized();

    $this->app['auth']->forgetGuards();
    $readOnly = CatalogTestHelper::adminHeaders($user, ['read-collections']);
    $this->getJson("/api/admin/v1/stores/{$store->id}/collections", $readOnly)->assertOk();

    $this->app['auth']->forgetGuards();
    $this->postJson("/api/admin/v1/stores/{$store->id}/collections",
        ['title' => 'Nope', 'type' => 'manual'], $readOnly)->assertForbidden();

    $stranger = \App\Models\User::factory()->create();
    $this->app['auth']->forgetGuards();
    $this->getJson("/api/admin/v1/stores/{$store->id}/collections", CatalogTestHelper::adminHeaders($stranger))->assertForbidden();
});
