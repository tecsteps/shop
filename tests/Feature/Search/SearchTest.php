<?php

use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->search = app(SearchService::class);
});

/**
 * Create an active product (synced into FTS via the observer) with neutral,
 * deterministic non-title fields so only the title drives matching. The FTS
 * index spans description/vendor/tags too, and the default factory seeds random
 * values that could otherwise pollute term-specific assertions.
 */
function searchableProduct(int $storeId, string $title, array $attributes = []): Product
{
    return Product::factory()->active()->create(array_merge([
        'store_id' => $storeId,
        'title' => $title,
        'description_html' => '<p>A product.</p>',
        'vendor' => 'Acme',
        'product_type' => 'Apparel',
        'tags' => ['catalog'],
    ], $attributes));
}

it('returns products matching search query', function () {
    searchableProduct($this->store->id, 'Blue Cotton T-Shirt');
    searchableProduct($this->store->id, 'Red Wool Sweater');

    $results = $this->search->search($this->store, 'cotton');

    expect($results->total())->toBe(1)
        ->and($results->getCollection()->first()->title)->toBe('Blue Cotton T-Shirt');
});

it('scopes search to current store', function () {
    searchableProduct($this->store->id, 'T-Shirt');

    $otherStore = Store::factory()->create(['organization_id' => $this->context['organization']->id]);
    searchableProduct($otherStore->id, 'T-Shirt Deluxe');

    $results = $this->search->search($this->store, 't-shirt');

    expect($results->total())->toBe(1)
        ->and($results->getCollection()->first()->store_id)->toBe($this->store->id);
});

it('returns empty for no matches', function () {
    searchableProduct($this->store->id, 'Blue Cotton T-Shirt');

    $results = $this->search->search($this->store, 'xyznonexistent');

    expect($results->total())->toBe(0);
});

it('logs search query for analytics', function () {
    searchableProduct($this->store->id, 'Blue Cotton T-Shirt');

    $this->search->search($this->store, 'cotton');

    $this->assertDatabaseHas('search_queries', [
        'store_id' => $this->store->id,
        'query' => 'cotton',
        'results_count' => 1,
    ]);
});

it('paginates search results', function () {
    for ($i = 1; $i <= 25; $i++) {
        searchableProduct($this->store->id, "Cotton Item {$i}");
    }

    $page1 = $this->search->search($this->store, 'cotton', perPage: 12, page: 1);
    $page2 = $this->search->search($this->store, 'cotton', perPage: 12, page: 2);
    $page3 = $this->search->search($this->store, 'cotton', perPage: 12, page: 3);

    expect($page1->total())->toBe(25)
        ->and($page1->count())->toBe(12)
        ->and($page2->count())->toBe(12)
        ->and($page3->count())->toBe(1);
});

it('finds products created through the observer without manual indexing', function () {
    $product = searchableProduct($this->store->id, 'Organic Linen Shirt');

    expect($this->search->search($this->store, 'linen')->getCollection()->pluck('id'))
        ->toContain($product->id);
});
