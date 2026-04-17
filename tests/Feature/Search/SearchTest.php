<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\SearchService;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->searchService = app(SearchService::class);
});

function createSearchableProduct(Store $store, array $overrides = []): Product
{
    $priceAmount = $overrides['price_amount'] ?? 2999;
    unset($overrides['price_amount']);

    $product = Product::withoutEvents(function () use ($store, $overrides) {
        return Product::factory()->create(array_merge([
            'store_id' => $store->id,
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ], $overrides));
    });

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $priceAmount,
        'is_default' => true,
    ]);

    app(SearchService::class)->syncProduct($product);

    return $product;
}

it('searches products by title', function () {
    createSearchableProduct($this->store, ['title' => 'Blue Running Shoes']);
    createSearchableProduct($this->store, ['title' => 'Red Hiking Boots']);

    $results = $this->searchService->search($this->store, 'Running');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Blue Running Shoes');
});

it('searches products by vendor', function () {
    createSearchableProduct($this->store, ['title' => 'Widget', 'vendor' => 'Acme Corp']);
    createSearchableProduct($this->store, ['title' => 'Gadget', 'vendor' => 'Beta Inc']);

    $results = $this->searchService->search($this->store, 'Acme');

    expect($results->total())->toBe(1);
});

it('searches products by tags', function () {
    createSearchableProduct($this->store, ['title' => 'Summer Dress', 'tags' => ['summer', 'sale']]);
    createSearchableProduct($this->store, ['title' => 'Winter Coat', 'tags' => ['winter']]);

    $results = $this->searchService->search($this->store, 'summer');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Summer Dress');
});

it('returns empty results for empty query', function () {
    createSearchableProduct($this->store, ['title' => 'Test Product']);

    $results = $this->searchService->search($this->store, '');

    expect($results->total())->toBe(0);
});

it('scopes search to the given store', function () {
    $otherStore = Store::factory()->create();

    createSearchableProduct($this->store, ['title' => 'My Widget']);
    createSearchableProduct($otherStore, ['title' => 'Their Widget']);

    $results = $this->searchService->search($this->store, 'Widget');

    expect($results->total())->toBe(1);
});

it('only returns active products', function () {
    createSearchableProduct($this->store, ['title' => 'Active Lamp', 'status' => ProductStatus::Active]);

    $draftProduct = Product::withoutEvents(function () {
        return Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => 'Draft Lamp',
            'status' => ProductStatus::Draft,
        ]);
    });
    $this->searchService->syncProduct($draftProduct);

    $results = $this->searchService->search($this->store, 'Lamp');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Active Lamp');
});

it('filters search results by vendor', function () {
    createSearchableProduct($this->store, ['title' => 'Phone A', 'vendor' => 'Apple']);
    createSearchableProduct($this->store, ['title' => 'Phone B', 'vendor' => 'Samsung']);

    $results = $this->searchService->search($this->store, 'Phone', ['vendor' => 'Apple']);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->vendor)->toBe('Apple');
});

it('filters search results by price range', function () {
    createSearchableProduct($this->store, ['title' => 'Cheap Item', 'price_amount' => 500]);
    createSearchableProduct($this->store, ['title' => 'Expensive Item', 'price_amount' => 50000]);

    $results = $this->searchService->search($this->store, 'Item', [
        'price_min' => 1000,
        'price_max' => 60000,
    ]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Expensive Item');
});

it('logs search queries', function () {
    createSearchableProduct($this->store, ['title' => 'Test Widget']);

    $this->searchService->search($this->store, 'Widget');

    $log = SearchQuery::withoutGlobalScopes()->where('store_id', $this->store->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->query)->toBe('Widget')
        ->and($log->results_count)->toBe(1);
});

it('syncs product into FTS index', function () {
    $product = Product::withoutEvents(function () {
        return Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => 'Unique Sync Test Item',
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ]);
    });

    ProductVariant::factory()->create(['product_id' => $product->id, 'is_default' => true, 'price_amount' => 1000]);

    $this->searchService->syncProduct($product);

    $results = $this->searchService->search($this->store, 'Unique Sync Test');
    expect($results->total())->toBe(1);
});

it('removes product from FTS index', function () {
    $product = createSearchableProduct($this->store, ['title' => 'Removable Product']);

    $this->searchService->removeProduct($product->id);

    $results = $this->searchService->search($this->store, 'Removable');
    expect($results->total())->toBe(0);
});

it('paginates search results', function () {
    for ($i = 1; $i <= 5; $i++) {
        createSearchableProduct($this->store, ['title' => "Paginated Widget {$i}"]);
    }

    $results = $this->searchService->search($this->store, 'Widget', [], 2);

    expect($results->perPage())->toBe(2)
        ->and($results->total())->toBe(5)
        ->and($results->items())->toHaveCount(2);
});
