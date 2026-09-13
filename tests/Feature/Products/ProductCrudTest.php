<?php

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Tests\Support\CatalogTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createCatalogProduct(array $overrides = []): array
{
    ['store' => $store, 'user' => $user] = test()->createStoreContext();

    $response = test()->postJson(
        "/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload($overrides),
        CatalogTestHelper::adminHeaders($user)
    );

    $response->assertCreated();

    return ['store' => $store, 'user' => $user, 'product' => Product::query()->findOrFail($response->json('data.id'))];
}

it('lists products for the current store', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    foreach (['Alpha Shirt', 'Beta Pants', 'Gamma Hat'] as $index => $title) {
        $this->postJson("/api/admin/v1/stores/{$store->id}/products",
            CatalogTestHelper::productPayload(['title' => $title, 'variants' => [
                [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => "SKU-{$index}"],
            ]]),
            CatalogTestHelper::adminHeaders($user)
        )->assertCreated();
    }

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/products", CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
    $response->assertJsonPath('meta.total', 3);
    $response->assertJsonStructure(['data' => [[
        'id', 'store_id', 'title', 'handle', 'status', 'vendor', 'product_type', 'tags',
        'variants_count', 'total_inventory', 'published_at', 'created_at', 'updated_at', 'featured_image',
    ]], 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    $response->assertJsonPath('data.0.variants_count', 1);
    $response->assertJsonPath('data.0.total_inventory', 50);
});

it('paginates product lists', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    foreach (range(1, 3) as $index) {
        $this->postJson("/api/admin/v1/stores/{$store->id}/products",
            CatalogTestHelper::productPayload(['title' => "Product {$index}", 'variants' => [
                [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => "SKU-{$index}"],
            ]]),
            CatalogTestHelper::adminHeaders($user)
        )->assertCreated();
    }

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/products?per_page=2&page=2", CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('meta.total', 3);
    $response->assertJsonPath('meta.current_page', 2);
    $response->assertJsonPath('meta.per_page', 2);
    $response->assertJsonPath('meta.last_page', 2);
});

it('filters products by status', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Draft One']), CatalogTestHelper::adminHeaders($user))->assertCreated();
    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Active One', 'status' => 'active', 'variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => 'SKU-ACTIVE'],
        ]]), CatalogTestHelper::adminHeaders($user))->assertCreated();

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/products?status=active", CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.title', 'Active One');
});

it('searches products by title vendor and sku', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Organic Cotton Hoodie']), $headers)->assertCreated();
    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Leather Belt', 'vendor' => 'Belt Co', 'variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => 'BELT-9'],
        ]]), $headers)->assertCreated();

    expect($this->getJson("/api/admin/v1/stores/{$store->id}/products?query=cotton", $headers)->json('data'))->toHaveCount(1);
    expect($this->getJson("/api/admin/v1/stores/{$store->id}/products?query=Belt%20Co", $headers)->json('data'))->toHaveCount(1);
    expect($this->getJson("/api/admin/v1/stores/{$store->id}/products?query=BELT-9", $headers)->json('data'))->toHaveCount(1);
    expect($this->getJson("/api/admin/v1/stores/{$store->id}/products?query=nonexistent", $headers)->json('data'))->toHaveCount(0);
});

it('filters products by collection', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createCatalogProduct();
    $headers = CatalogTestHelper::adminHeaders($user);

    $collection = Collection::factory()->create(['store_id' => $store->id]);
    $collection->products()->attach($product->id, ['position' => 0]);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Unlisted Item', 'variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => 'SKU-OTHER'],
        ]]), $headers)->assertCreated();

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/products?collection_id={$collection->id}", $headers);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.id', $product->id);
});

it('sorts product lists', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);

    foreach (['Zebra', 'Apple'] as $index => $title) {
        $this->postJson("/api/admin/v1/stores/{$store->id}/products",
            CatalogTestHelper::productPayload(['title' => $title, 'variants' => [
                [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => "SKU-{$index}"],
            ]]), $headers)->assertCreated();
    }

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/products?sort=title_asc", $headers);

    $response->assertOk();
    $response->assertJsonPath('data.0.title', 'Apple');
    $response->assertJsonPath('data.1.title', 'Zebra');
});

it('creates a product with options variants inventory and collections', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $store->id]);

    $response = $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['collections' => [$collection->id]]),
        CatalogTestHelper::adminHeaders($user));

    $response->assertCreated();
    $response->assertJsonPath('data.title', 'Classic T-Shirt');
    $response->assertJsonPath('data.handle', 'classic-t-shirt');
    $response->assertJsonPath('data.status', 'draft');
    $response->assertJsonPath('data.options.0.name', 'Size');
    $response->assertJsonPath('data.options.0.values.0.value', 'Small');
    $response->assertJsonPath('data.variants.0.sku', 'TSH-S');
    $response->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 50);
    $response->assertJsonPath('data.variants.0.inventory.policy', 'deny');
    $response->assertJsonPath('data.variants.0.option_values.0.value', 'Small');
    $response->assertJsonPath('data.collections.0.id', $collection->id);

    $product = Product::query()->findOrFail($response->json('data.id'));
    expect($product->productOptions()->count())->toBe(1)
        ->and($product->productVariants()->count())->toBe(1)
        ->and(InventoryItem::query()->where('store_id', $store->id)->count())->toBe(1)
        ->and($product->collections()->count())->toBe(1);
});

it('generates unique handles with suffix on collision', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);

    $first = $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Summer T-Shirt']), $headers);
    $second = $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Summer T-Shirt', 'variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => 'SKU-2'],
        ]]), $headers);

    $first->assertCreated()->assertJsonPath('data.handle', 'summer-t-shirt');
    $second->assertCreated()->assertJsonPath('data.handle', 'summer-t-shirt-1');
});

it('rejects duplicate handles and skus', function () {
    ['store' => $store, 'user' => $user] = createCatalogProduct();
    $headers = CatalogTestHelper::adminHeaders($user);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Other', 'handle' => 'classic-t-shirt', 'variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => 'SKU-NEW'],
        ]]), $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['handle']);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Other']), $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['variants.0.sku']);
});

it('validates product creation payloads', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products", [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'variants']);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'price_amount' => 2500, 'compare_at_amount' => 1000],
        ]]), $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['variants.0.compare_at_amount']);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['handle' => 'NOT A HANDLE']), $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['handle']);
});

it('shows a single product with all relations', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createCatalogProduct();

    $response = $this->getJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    $response->assertJsonPath('data.id', $product->id);
    $response->assertJsonStructure(['data' => [
        'id', 'store_id', 'title', 'handle', 'description_html', 'vendor', 'product_type',
        'status', 'tags', 'published_at', 'created_at', 'updated_at', 'options', 'variants', 'media', 'collections',
    ]]);
    $response->assertJsonPath('data.variants.0.inventory.available', 50);
});

it('updates a product partially', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createCatalogProduct();

    $response = $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", [
        'title' => 'Classic Cotton T-Shirt',
        'tags' => ['organic', 'cotton', 'bestseller'],
    ], CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Classic Cotton T-Shirt');
    $response->assertJsonPath('data.tags', ['organic', 'cotton', 'bestseller']);
    $response->assertJsonPath('data.handle', 'classic-t-shirt');
});

it('transitions draft to active and sets published at', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createCatalogProduct();

    expect($product->published_at)->toBeNull();

    $response = $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", [
        'status' => 'active',
    ], CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    $response->assertJsonPath('data.status', 'active');
    expect($response->json('data.published_at'))->not->toBeNull();
    expect($product->fresh()->status)->toBe(ProductStatus::Active);
});

it('rejects draft to active without a priced variant', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $headers = CatalogTestHelper::adminHeaders($user);

    $created = $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'price_amount' => 0, 'compare_at_amount' => null],
        ]]), $headers);
    $created->assertCreated();

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$created->json('data.id')}", [
        'status' => 'active',
    ], $headers)->assertUnprocessable();

    expect(Product::query()->findOrFail($created->json('data.id'))->status)->toBe(ProductStatus::Draft);
});

it('transitions active to archived and republishes', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createCatalogProduct(['status' => 'active']);
    $headers = CatalogTestHelper::adminHeaders($user);

    expect($product->fresh()->status)->toBe(ProductStatus::Active);

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", ['status' => 'archived'], $headers)
        ->assertOk()->assertJsonPath('data.status', 'archived');

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", ['status' => 'active'], $headers)
        ->assertOk()->assertJsonPath('data.status', 'active');
});

it('prevents reverting to draft when order lines exist', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createCatalogProduct(['status' => 'active']);
    $headers = CatalogTestHelper::adminHeaders($user);

    OrderLine::factory()->create([
        'product_id' => $product->id,
        'variant_id' => $product->productVariants()->first()->id,
    ]);

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", ['status' => 'draft'], $headers)
        ->assertUnprocessable();

    expect($product->fresh()->status)->toBe(ProductStatus::Active);
});

it('archives a product on delete', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createCatalogProduct(['status' => 'active']);

    $response = $this->deleteJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", [], CatalogTestHelper::adminHeaders($user));

    $response->assertOk();
    $response->assertJsonPath('data.id', $product->id);
    $response->assertJsonPath('data.status', 'archived');
    expect($product->fresh()->status)->toBe(ProductStatus::Archived);

    $this->deleteJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}", [], CatalogTestHelper::adminHeaders($user))
        ->assertOk()->assertJsonPath('data.status', 'archived');
});

it('hard deletes draft products without order references via the service', function () {
    ['store' => $store] = $this->createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($store, CatalogTestHelper::productPayload());
    $service->delete($product->fresh());

    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();
    expect(ProductVariant::query()->where('product_id', $product->id)->exists())->toBeFalse();
});

it('prevents hard deleting products with order references or non-draft status', function () {
    ['store' => $store] = $this->createStoreContext();
    $service = app(ProductService::class);

    $ordered = $service->create($store, CatalogTestHelper::productPayload());
    OrderLine::factory()->create([
        'product_id' => $ordered->id,
        'variant_id' => $ordered->productVariants()->first()->id,
    ]);

    expect(fn () => $service->delete($ordered->fresh()))->toThrow(InvalidProductTransitionException::class);

    $active = $service->create($store, CatalogTestHelper::productPayload([
        'title' => 'Active Item',
        'status' => 'active',
        'variants' => [[...CatalogTestHelper::productPayload()['variants'][0], 'sku' => 'SKU-ACTIVE-2']],
    ]));

    expect(fn () => $service->delete($active->fresh()))->toThrow(InvalidProductTransitionException::class);
});

it('isolates products by store', function () {
    ['store' => $storeA, 'user' => $userA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    $headersA = CatalogTestHelper::adminHeaders($userA);

    $other = app(ProductService::class)->create($storeB, CatalogTestHelper::productPayload());

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/products/{$other->id}", $headersA)->assertNotFound();
    $this->putJson("/api/admin/v1/stores/{$storeA->id}/products/{$other->id}", ['title' => 'Hijack'], $headersA)->assertNotFound();
    $this->deleteJson("/api/admin/v1/stores/{$storeA->id}/products/{$other->id}", [], $headersA)->assertNotFound();

    $response = $this->getJson("/api/admin/v1/stores/{$storeA->id}/products", $headersA);
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});

it('enforces authentication and abilities on product endpoints', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $this->getJson("/api/admin/v1/stores/{$store->id}/products")->assertUnauthorized();
    $this->postJson("/api/admin/v1/stores/{$store->id}/products", CatalogTestHelper::productPayload())->assertUnauthorized();

    $readOnly = CatalogTestHelper::adminHeaders($user, ['read-products']);
    $this->getJson("/api/admin/v1/stores/{$store->id}/products", $readOnly)->assertOk();
    $this->app['auth']->forgetGuards();
    $this->postJson("/api/admin/v1/stores/{$store->id}/products", CatalogTestHelper::productPayload(), $readOnly)->assertForbidden();

    $stranger = \App\Models\User::factory()->create();
    $this->app['auth']->forgetGuards();
    $this->getJson("/api/admin/v1/stores/{$store->id}/products", CatalogTestHelper::adminHeaders($stranger))->assertForbidden();

    $this->app['auth']->forgetGuards();
    $this->getJson('/api/admin/v1/stores/999999/products', CatalogTestHelper::adminHeaders($user))->assertNotFound();
});

it('allows staff to manage products but blocks support writes', function () {
    ['store' => $store] = $this->createStoreContext();

    $staff = \App\Models\User::factory()->create();
    \App\Models\StoreUser::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id, 'role' => \App\Enums\StoreUserRole::Staff]);

    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(), CatalogTestHelper::adminHeaders($staff))->assertCreated();

    $support = \App\Models\User::factory()->create();
    \App\Models\StoreUser::factory()->create(['store_id' => $store->id, 'user_id' => $support->id, 'role' => \App\Enums\StoreUserRole::Support]);

    $this->app['auth']->forgetGuards();
    $this->getJson("/api/admin/v1/stores/{$store->id}/products", CatalogTestHelper::adminHeaders($support))->assertOk();
    $this->app['auth']->forgetGuards();
    $this->postJson("/api/admin/v1/stores/{$store->id}/products",
        CatalogTestHelper::productPayload(['title' => 'Support Item', 'variants' => [
            [...CatalogTestHelper::productPayload()['variants'][0], 'sku' => 'SKU-SUP'],
        ]]), CatalogTestHelper::adminHeaders($support))->assertForbidden();
});
