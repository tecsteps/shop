<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(SearchService::class);
});

/**
 * Create an active product with a default variant and sync it to FTS5 index.
 *
 * @param  array<string, mixed>  $attributes
 */
function createSearchableProduct(Store $store, SearchService $service, array $attributes = []): Product
{
    $product = Product::withoutGlobalScopes()->create(array_merge([
        'store_id' => $store->id,
        'title' => 'Test Product',
        'handle' => 'test-product-'.uniqid(),
        'status' => ProductStatus::Active,
        'description_html' => '<p>A test product description</p>',
        'vendor' => 'TestVendor',
        'product_type' => 'Clothing',
        'tags' => ['summer', 'sale'],
        'published_at' => now(),
    ], $attributes));

    ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => $attributes['price_amount'] ?? 2999,
        'currency' => 'USD',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);

    $service->syncProduct($product);

    return $product;
}

test('returns products matching search query', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Blue Cotton T-Shirt',
        'description_html' => '<p>Comfortable cotton t-shirt</p>',
    ]);

    createSearchableProduct($this->store, $this->service, [
        'title' => 'Red Wool Sweater',
        'description_html' => '<p>Warm wool sweater</p>',
    ]);

    $results = $this->service->search($this->store, 'cotton');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Blue Cotton T-Shirt');
});

test('scopes search to current store', function () {
    $storeB = Store::factory()->create();

    createSearchableProduct($this->store, $this->service, [
        'title' => 'T-Shirt',
    ]);

    $productB = Product::withoutGlobalScopes()->create([
        'store_id' => $storeB->id,
        'title' => 'T-Shirt Deluxe',
        'handle' => 't-shirt-deluxe',
        'status' => ProductStatus::Active,
        'published_at' => now(),
        'tags' => [],
    ]);
    ProductVariant::create([
        'product_id' => $productB->id,
        'price_amount' => 1999,
        'currency' => 'USD',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);
    $this->service->syncProduct($productB);

    $results = $this->service->search($this->store, 't-shirt');

    expect($results->total())->toBe(1);
    expect($results->first()->store_id)->toBe($this->store->id);
});

test('returns empty for no matches', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Blue Cotton T-Shirt',
    ]);

    $results = $this->service->search($this->store, 'xyznonexistent');

    expect($results->total())->toBe(0);
});

test('logs search query for analytics', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Blue Cotton T-Shirt',
    ]);

    $this->service->search($this->store, 'cotton');

    $log = SearchQuery::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->query)->toBe('cotton');
    expect($log->results_count)->toBe(1);
});

test('paginates search results', function () {
    for ($i = 1; $i <= 25; $i++) {
        createSearchableProduct($this->store, $this->service, [
            'title' => "Widget Product $i",
            'handle' => "widget-product-$i",
        ]);
    }

    $page1 = $this->service->search($this->store, 'widget', [], 12);

    expect($page1->total())->toBe(25);
    expect($page1->count())->toBe(12);
    expect($page1->lastPage())->toBe(3);
});

test('filters by vendor', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Premium Shoes',
        'vendor' => 'Nike',
    ]);

    createSearchableProduct($this->store, $this->service, [
        'title' => 'Budget Shoes',
        'vendor' => 'Generic',
    ]);

    $results = $this->service->search($this->store, 'shoes', ['vendors' => ['Nike']]);

    expect($results->total())->toBe(1);
    expect($results->first()->vendor)->toBe('Nike');
});

test('filters by price range', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Cheap Widget',
        'price_amount' => 999,
    ]);

    createSearchableProduct($this->store, $this->service, [
        'title' => 'Expensive Widget',
        'price_amount' => 9999,
    ]);

    $results = $this->service->search($this->store, 'widget', ['max_price' => 2000]);

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Cheap Widget');
});

test('filters by minimum price', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Cheap Gadget',
        'price_amount' => 500,
    ]);

    createSearchableProduct($this->store, $this->service, [
        'title' => 'Expensive Gadget',
        'price_amount' => 5000,
    ]);

    $results = $this->service->search($this->store, 'gadget', ['min_price' => 3000]);

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Expensive Gadget');
});

test('excludes draft products from search results', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Active Phone',
    ]);

    $draftProduct = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Draft Phone',
        'handle' => 'draft-phone',
        'status' => ProductStatus::Draft,
        'tags' => [],
    ]);
    ProductVariant::create([
        'product_id' => $draftProduct->id,
        'price_amount' => 999,
        'currency' => 'USD',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);
    $this->service->syncProduct($draftProduct);

    $results = $this->service->search($this->store, 'phone');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Active Phone');
});

test('excludes unpublished products from search results', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Published Lamp',
    ]);

    $unpublished = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Unpublished Lamp',
        'handle' => 'unpublished-lamp',
        'status' => ProductStatus::Active,
        'published_at' => null,
        'tags' => [],
    ]);
    ProductVariant::create([
        'product_id' => $unpublished->id,
        'price_amount' => 999,
        'currency' => 'USD',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);
    $this->service->syncProduct($unpublished);

    $results = $this->service->search($this->store, 'lamp');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Published Lamp');
});

test('searches by description', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Basic Hat',
        'description_html' => '<p>Made with premium organic cotton material</p>',
    ]);

    $results = $this->service->search($this->store, 'organic');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Basic Hat');
});

test('searches by vendor name', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Running Shoes',
        'vendor' => 'Adidas',
    ]);

    $results = $this->service->search($this->store, 'adidas');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Running Shoes');
});

test('searches by tags', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Summer Dress',
        'tags' => ['beachwear', 'tropical'],
    ]);

    $results = $this->service->search($this->store, 'tropical');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Summer Dress');
});

test('returns empty for empty query string', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Some Product',
    ]);

    $results = $this->service->search($this->store, '');

    expect($results->total())->toBe(0);
});

test('sorts by price ascending', function () {
    createSearchableProduct($this->store, $this->service, [
        'title' => 'Expensive Backpack',
        'price_amount' => 9999,
    ]);

    createSearchableProduct($this->store, $this->service, [
        'title' => 'Cheap Backpack',
        'price_amount' => 1999,
    ]);

    $results = $this->service->search($this->store, 'backpack', ['sort' => 'price-asc']);

    expect($results->first()->title)->toBe('Cheap Backpack');
});

test('sorts by newest', function () {
    $older = createSearchableProduct($this->store, $this->service, [
        'title' => 'Old Jacket',
    ]);
    Product::withoutGlobalScopes()->where('id', $older->id)->update(['created_at' => '2024-01-01 00:00:00']);

    $newer = createSearchableProduct($this->store, $this->service, [
        'title' => 'New Jacket',
    ]);
    Product::withoutGlobalScopes()->where('id', $newer->id)->update(['created_at' => '2025-06-01 00:00:00']);

    $results = $this->service->search($this->store, 'jacket', ['sort' => 'newest']);

    expect($results->first()->title)->toBe('New Jacket');
});
