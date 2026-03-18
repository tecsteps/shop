<?php

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(SearchService::class);
});

function createSearchableProduct(mixed $store, string $title, array $overrides = []): Product
{
    $product = Product::withoutGlobalScopes()->create(array_merge([
        'store_id' => $store->id,
        'title' => $title,
        'handle' => \Illuminate\Support\Str::slug($title).'-'.uniqid(),
        'status' => ProductStatus::Active,
        'description_html' => '<p>A great product</p>',
        'vendor' => 'TestVendor',
        'product_type' => 'TestType',
        'tags' => [],
        'published_at' => now(),
    ], $overrides));

    ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'SKU-'.uniqid(),
        'price_amount' => $overrides['price_amount'] ?? 1000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
    ]);

    return $product;
}

it('finds products by title via FTS5', function () {
    createSearchableProduct($this->store, 'Running Shoes Pro');
    createSearchableProduct($this->store, 'Cotton T-Shirt');

    $results = $this->service->search($this->store, 'Running');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Running Shoes Pro');
});

it('finds products by description', function () {
    createSearchableProduct($this->store, 'Simple Product', [
        'description_html' => '<p>Premium leather handbag with golden buckle</p>',
    ]);

    $results = $this->service->search($this->store, 'leather handbag');

    expect($results->total())->toBe(1);
});

it('finds products by vendor', function () {
    createSearchableProduct($this->store, 'Some Product', ['vendor' => 'Nike']);
    createSearchableProduct($this->store, 'Another Product', ['vendor' => 'Adidas']);

    $results = $this->service->search($this->store, 'Nike');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->vendor)->toBe('Nike');
});

it('finds products by product type', function () {
    createSearchableProduct($this->store, 'A Dress', ['product_type' => 'Dresses']);
    createSearchableProduct($this->store, 'A Shirt', ['product_type' => 'Shirts']);

    $results = $this->service->search($this->store, 'Dresses');

    expect($results->total())->toBe(1);
});

it('finds products by tags', function () {
    createSearchableProduct($this->store, 'Tagged Product', ['tags' => ['summer', 'sale']]);
    createSearchableProduct($this->store, 'Winter Product', ['tags' => ['winter']]);

    $results = $this->service->search($this->store, 'summer');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Tagged Product');
});

it('scopes search results to the current store', function () {
    createSearchableProduct($this->store, 'Store1 Shoes');

    $otherContext = createStoreContext('other-store.test');
    createSearchableProduct($otherContext['store'], 'Store2 Shoes');

    $results = $this->service->search($this->store, 'Shoes');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Store1 Shoes');
});

it('excludes non-active products from search results', function () {
    createSearchableProduct($this->store, 'Active Product');
    createSearchableProduct($this->store, 'Draft Product', ['status' => ProductStatus::Draft, 'published_at' => null]);
    createSearchableProduct($this->store, 'Archived Product', ['status' => ProductStatus::Archived]);

    $results = $this->service->search($this->store, 'Product');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Active Product');
});

it('supports prefix matching on the last token', function () {
    createSearchableProduct($this->store, 'Running Shoes Pro');

    $results = $this->service->search($this->store, 'Runn');

    expect($results->total())->toBe(1);
});

it('filters search results by vendor', function () {
    createSearchableProduct($this->store, 'Nike Shoes', ['vendor' => 'Nike']);
    createSearchableProduct($this->store, 'Adidas Shoes', ['vendor' => 'Adidas']);

    $results = $this->service->search($this->store, 'Shoes', ['vendor' => 'Nike']);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->vendor)->toBe('Nike');
});

it('filters search results by price range', function () {
    createSearchableProduct($this->store, 'Cheap Shoes', ['price_amount' => 1000]);
    createSearchableProduct($this->store, 'Expensive Shoes', ['price_amount' => 10000]);

    $results = $this->service->search($this->store, 'Shoes', ['min_price' => 5000, 'max_price' => 15000]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Expensive Shoes');
});

it('filters search results by collection', function () {
    $p1 = createSearchableProduct($this->store, 'Collection Shoes');
    $p2 = createSearchableProduct($this->store, 'Other Shoes');

    $collection = Collection::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Running',
        'handle' => 'running',
        'status' => 'active',
        'type' => 'manual',
    ]);
    $collection->products()->attach($p1->id, ['position' => 0]);

    $results = $this->service->search($this->store, 'Shoes', ['collection_id' => $collection->id]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Collection Shoes');
});

it('sorts results by price ascending', function () {
    createSearchableProduct($this->store, 'Expensive Item', ['price_amount' => 10000]);
    createSearchableProduct($this->store, 'Cheap Item', ['price_amount' => 500]);

    $results = $this->service->search($this->store, 'Item', ['sort' => 'price_asc']);

    expect($results->items()[0]->title)->toBe('Cheap Item')
        ->and($results->items()[1]->title)->toBe('Expensive Item');
});

it('sorts results by price descending', function () {
    createSearchableProduct($this->store, 'Expensive Item', ['price_amount' => 10000]);
    createSearchableProduct($this->store, 'Cheap Item', ['price_amount' => 500]);

    $results = $this->service->search($this->store, 'Item', ['sort' => 'price_desc']);

    expect($results->items()[0]->title)->toBe('Expensive Item')
        ->and($results->items()[1]->title)->toBe('Cheap Item');
});

it('sorts results by newest', function () {
    $old = createSearchableProduct($this->store, 'Old Item');
    Product::withoutGlobalScopes()->where('id', $old->id)->update(['created_at' => now()->subDays(5)]);

    $new = createSearchableProduct($this->store, 'New Item');

    $results = $this->service->search($this->store, 'Item', ['sort' => 'newest']);

    expect($results->items()[0]->title)->toBe('New Item');
});

it('paginates search results', function () {
    for ($i = 1; $i <= 5; $i++) {
        createSearchableProduct($this->store, "Paginated Widget {$i}");
    }

    $results = $this->service->search($this->store, 'Widget', [], 2);

    expect($results->perPage())->toBe(2)
        ->and($results->total())->toBe(5)
        ->and($results->items())->toHaveCount(2);
});

it('logs search queries', function () {
    createSearchableProduct($this->store, 'Logged Product');

    $this->service->search($this->store, 'Logged');

    $log = SearchQuery::withoutGlobalScopes()->where('store_id', $this->store->id)->first();

    expect($log)->not->toBeNull()
        ->and($log->query)->toBe('Logged')
        ->and($log->results_count)->toBe(1);
});

it('handles empty search query gracefully', function () {
    $results = $this->service->search($this->store, '');

    expect($results->total())->toBe(0);
});

it('handles special characters in search query', function () {
    createSearchableProduct($this->store, 'Product with special chars');

    $results = $this->service->search($this->store, 'Product"with*special');

    expect($results->total())->toBe(1);
});

it('syncs product to FTS index on create via observer', function () {
    $product = createSearchableProduct($this->store, 'Observer Created');

    $ftsRow = \Illuminate\Support\Facades\DB::table('products_fts')
        ->where('product_id', $product->id)
        ->first();

    expect($ftsRow)->not->toBeNull()
        ->and($ftsRow->title)->toBe('Observer Created');
});

it('updates FTS index on product update via observer', function () {
    $product = createSearchableProduct($this->store, 'Original Title');
    $product->update(['title' => 'Updated Title']);

    $results = $this->service->search($this->store, 'Updated');

    expect($results->total())->toBe(1);

    $oldResults = $this->service->search($this->store, 'Original');
    expect($oldResults->total())->toBe(0);
});

it('removes from FTS index on product delete via observer', function () {
    $product = createSearchableProduct($this->store, 'Deletable Product');
    $product->delete();

    $results = $this->service->search($this->store, 'Deletable');

    expect($results->total())->toBe(0);
});

it('strips HTML from description before indexing', function () {
    createSearchableProduct($this->store, 'HTML Product', [
        'description_html' => '<h1>Bold</h1><p>Leather <strong>material</strong></p>',
    ]);

    $ftsRow = \Illuminate\Support\Facades\DB::table('products_fts')
        ->where('title', 'HTML Product')
        ->first();

    expect($ftsRow->description)->not->toContain('<h1>')
        ->and($ftsRow->description)->not->toContain('<strong>')
        ->and($ftsRow->description)->toContain('Leather')
        ->and($ftsRow->description)->toContain('material');
});

it('reindexes all products for a store', function () {
    createSearchableProduct($this->store, 'Reindex Product One');
    createSearchableProduct($this->store, 'Reindex Product Two');

    // Clear the FTS index manually
    \Illuminate\Support\Facades\DB::statement('DELETE FROM products_fts WHERE store_id = ?', [$this->store->id]);

    // Verify it is empty
    $emptyResults = $this->service->search($this->store, 'Reindex');
    expect($emptyResults->total())->toBe(0);

    // Reindex
    $count = $this->service->reindexStore($this->store);

    expect($count)->toBe(2);

    $results = $this->service->search($this->store, 'Reindex');
    expect($results->total())->toBe(2);
});
