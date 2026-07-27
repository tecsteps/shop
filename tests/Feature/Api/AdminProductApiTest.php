<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\ApiTokenService;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->tokens = app(ApiTokenService::class);
    $this->token = $this->tokens->create($this->user, 'API', ['read-products', 'write-products']);
});

function productPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Classic T-Shirt',
        'description_html' => '<p>A comfortable cotton t-shirt.</p>',
        'vendor' => 'Acme Apparel',
        'product_type' => 'Apparel',
        'status' => 'draft',
        'tags' => ['organic', 'cotton'],
        'options' => [
            ['name' => 'Color', 'position' => 1],
            ['name' => 'Size', 'position' => 2],
        ],
        'variants' => [
            ['sku' => 'TSH-BLU-S', 'price_amount' => 2500, 'option_values' => [['option_name' => 'Color', 'value' => 'Blue'], ['option_name' => 'Size', 'value' => 'Small']], 'inventory' => ['quantity_on_hand' => 50, 'policy' => 'deny']],
            ['sku' => 'TSH-BLU-L', 'price_amount' => 2500, 'option_values' => [['option_name' => 'Color', 'value' => 'Blue'], ['option_name' => 'Size', 'value' => 'Large']], 'inventory' => ['quantity_on_hand' => 30]],
            ['sku' => 'TSH-RED-S', 'price_amount' => 2700, 'option_values' => [['option_name' => 'Color', 'value' => 'Red'], ['option_name' => 'Size', 'value' => 'Small']]],
            ['sku' => 'TSH-RED-L', 'price_amount' => 2700, 'option_values' => [['option_name' => 'Color', 'value' => 'Red'], ['option_name' => 'Size', 'value' => 'Large']]],
        ],
    ], $overrides);
}

test('lists products with authentication', function () {
    Product::factory()->active()->withVariants(2)->count(3)->create(['store_id' => $this->store->id]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'store_id', 'title', 'handle', 'status', 'tags', 'variants_count', 'total_inventory', 'featured_image']],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('data.0.variants_count', 2);
});

test('returns 401 without token', function () {
    $this->getJson("/api/admin/v1/stores/{$this->store->id}/products")
        ->assertUnauthorized();
});

test('filters products by status', function () {
    Product::factory()->active()->count(2)->create(['store_id' => $this->store->id]);
    Product::factory()->draft()->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products?status=active")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

test('searches products by title', function () {
    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Organic Cotton Hoodie']);
    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Leather Wallet']);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products?query=cotton");

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.title'))->toBe('Organic Cotton Hoodie');
});

test('filters products by collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $inCollection = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $inCollection->collections()->attach($collection->id, ['position' => 0]);
    Product::factory()->active()->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products?collection_id={$collection->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inCollection->id);
});

test('paginates results', function () {
    Product::factory()->count(30)->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products")
        ->assertOk()
        ->assertJsonCount(25, 'data')
        ->assertJsonPath('meta.total', 30)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.per_page', 25);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products?per_page=10&page=3")
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.current_page', 3);
});

test('creates a product via API with nested options, variants, inventory and collections', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", productPayload(['collections' => [$collection->id]]));

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Classic T-Shirt')
        ->assertJsonPath('data.handle', 'classic-t-shirt')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.collections.0.id', $collection->id);

    expect($response->json('data.variants'))->toHaveCount(4)
        ->and($response->json('data.options'))->toHaveCount(2)
        ->and($response->json('data.options.0.values'))->toHaveCount(2);

    $blueSmall = collect($response->json('data.variants'))->firstWhere('sku', 'TSH-BLU-S');

    expect($blueSmall['price_amount'])->toBe(2500)
        ->and($blueSmall['inventory']['quantity_on_hand'])->toBe(50)
        ->and($blueSmall['option_values'])->toBe([
            ['option_name' => 'Color', 'value' => 'Blue'],
            ['option_name' => 'Size', 'value' => 'Small'],
        ]);
});

test('rejects a duplicate sku within the store', function () {
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
            'title' => 'First Product',
            'variants' => [['sku' => 'DUP-1', 'price_amount' => 100]],
        ])
        ->assertCreated();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
            'title' => 'Second Product',
            'variants' => [['sku' => 'DUP-1', 'price_amount' => 200]],
        ])
        ->assertUnprocessable();
});

test('creates a product without options gets a single default variant', function () {
    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", [
            'title' => 'Gift Card',
            'variants' => [['sku' => 'GIFT-25', 'price_amount' => 2500, 'inventory' => ['quantity_on_hand' => 100]]],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Gift Card');

    expect($response->json('data.variants'))->toHaveCount(1)
        ->and($response->json('data.variants.0.is_default'))->toBeTrue()
        ->and($response->json('data.variants.0.inventory.quantity_on_hand'))->toBe(100);
});

test('gets a single product with all relations', function () {
    $product = Product::factory()->active()->withVariants(2)->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'title', 'handle', 'options', 'variants' => [['id', 'sku', 'price_amount', 'option_values', 'inventory']], 'media', 'collections'],
        ])
        ->assertJsonPath('data.id', $product->id);
});

test('updates a product via API', function () {
    $product = Product::factory()->draft()->withVariants(1, ['price_amount' => 1000])->create(['store_id' => $this->store->id]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->putJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}", [
            'title' => 'Classic Cotton T-Shirt',
            'status' => 'active',
            'tags' => ['organic', 'cotton', 'bestseller'],
        ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Classic Cotton T-Shirt')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.tags', ['organic', 'cotton', 'bestseller']);

    expect($response->json('data.published_at'))->not->toBeNull();
});

test('deletes (archives) a product via API', function () {
    $product = Product::factory()->active()->withVariants(1)->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->deleteJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.status', 'archived');

    expect($product->fresh()->status)->toBe(\App\Enums\ProductStatus::Archived);
});

test('requires write-products ability for mutations', function () {
    $readOnly = $this->tokens->create($this->user, 'Read only', ['read-products']);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$readOnly)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products", productPayload())
        ->assertForbidden();

    $this->withHeader('Authorization', 'Bearer '.$readOnly)
        ->putJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}", ['title' => 'Nope'])
        ->assertForbidden();

    $this->withHeader('Authorization', 'Bearer '.$readOnly)
        ->deleteJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}")
        ->assertForbidden();
});

test('scopes products to the requested store', function () {
    $otherStore = $this->createStore();
    $otherProduct = Product::factory()->active()->create(['store_id' => $otherStore->id, 'title' => 'Other Store Product']);
    Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'My Product']);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/products");

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.title'))->toBe('My Product');

    // Products of another store are not addressable either.
    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$otherStore->id}/products/{$otherProduct->id}")
        ->assertForbidden();
});

test('rejects users without store membership', function () {
    $otherStore = $this->createStore();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$otherStore->id}/products")
        ->assertForbidden();
});

test('presigns a media upload', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}/media/presign-upload", [
            'filename' => 'tshirt-front.jpg',
            'content_type' => 'image/jpeg',
            'byte_size' => 245000,
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['upload_url', 'method', 'headers' => ['Content-Type'], 'storage_key', 'media_id', 'expires_at'])
        ->assertJsonPath('method', 'PUT')
        ->assertJsonPath('headers.Content-Type', 'image/jpeg');

    $media = ProductMedia::query()->sole();

    expect($media->product_id)->toBe($product->id)
        ->and($media->storage_key)->toBe($response->json('storage_key'))
        ->and($media->status)->toBe(\App\Enums\MediaStatus::Processing)
        ->and($media->byte_size)->toBe(245000);
});

test('rejects presign with invalid content type', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/products/{$product->id}/media/presign-upload", [
            'filename' => 'evil.exe',
            'content_type' => 'application/x-msdownload',
            'byte_size' => 100,
        ])
        ->assertUnprocessable();
});
