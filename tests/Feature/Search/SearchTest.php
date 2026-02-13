<?php

use App\Models\Organization;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->searchService = app(SearchService::class);
});

it('returns products matching search query', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Running Shoes',
        'vendor' => 'Nike',
        'product_type' => 'Footwear',
    ]);
    $this->searchService->syncProduct($product);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Hat',
        'vendor' => 'Adidas',
        'product_type' => 'Accessories',
    ]);

    $results = $this->searchService->search($this->store, 'Running Shoes');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($product->id);
});

it('scopes search to current store', function () {
    $org = Organization::factory()->create();
    $otherStore = Store::factory()->for($org)->create();

    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Special Widget',
    ]);
    $this->searchService->syncProduct($product);

    $otherProduct = Product::factory()->create([
        'store_id' => $otherStore->id,
        'title' => 'Special Widget',
    ]);
    $this->searchService->syncProduct($otherProduct);

    $results = $this->searchService->search($this->store, 'Special Widget');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($product->id);
});

it('returns empty for no matches', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Running Shoes',
    ]);

    $results = $this->searchService->search($this->store, 'nonexistentxyz');

    expect($results->total())->toBe(0);
});

it('logs search query for analytics', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Trackable Product',
    ]);
    $this->searchService->syncProduct($product);

    $this->searchService->search($this->store, 'Trackable');

    $logged = SearchQuery::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($logged)->not->toBeNull()
        ->and($logged->query)->toBe('Trackable')
        ->and($logged->results_count)->toBe(1);
});

it('paginates search results', function () {
    for ($i = 1; $i <= 15; $i++) {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Paginated Item $i",
        ]);
        $this->searchService->syncProduct($product);
    }

    $results = $this->searchService->search($this->store, 'Paginated', [], 5);

    expect($results->total())->toBe(15)
        ->and($results->perPage())->toBe(5)
        ->and($results->count())->toBe(5);
});
