<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->searchService = app(SearchService::class);
});

it('returns products matching search query', function () {
    $product1 = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Cotton T-Shirt',
        'status' => 'active',
    ]);
    $product2 = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Wool Sweater',
        'status' => 'active',
    ]);

    $this->searchService->syncProduct($product1);
    $this->searchService->syncProduct($product2);

    $results = $this->searchService->search($this->store, 'cotton');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Blue Cotton T-Shirt');
});

it('scopes search to current store', function () {
    $otherContext = createStoreContext('other-store.test');

    $product1 = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Store A T-Shirt',
        'status' => 'active',
    ]);
    $product2 = Product::factory()->create([
        'store_id' => $otherContext['store']->id,
        'title' => 'Store B T-Shirt Deluxe',
        'status' => 'active',
    ]);

    $this->searchService->syncProduct($product1);
    $this->searchService->syncProduct($product2);

    $results = $this->searchService->search($this->store, 'Shirt');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Store A T-Shirt');
});

it('returns empty for no matches', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Cotton T-Shirt',
        'status' => 'active',
    ]);

    $this->searchService->syncProduct($product);

    $results = $this->searchService->search($this->store, 'xyznonexistent');

    expect($results->total())->toBe(0);
});

it('logs search query for analytics', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Cotton T-Shirt',
        'status' => 'active',
    ]);

    $this->searchService->syncProduct($product);

    $this->searchService->search($this->store, 'cotton');

    $searchQuery = SearchQuery::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($searchQuery)->not->toBeNull()
        ->and($searchQuery->query)->toBe('cotton')
        ->and($searchQuery->results_count)->toBe(1);
});

it('paginates search results', function () {
    for ($i = 0; $i < 25; $i++) {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Summer Product $i",
            'status' => 'active',
        ]);
        $this->searchService->syncProduct($product);
    }

    $page1 = $this->searchService->search($this->store, 'Summer', [], 12);

    expect($page1->total())->toBe(25)
        ->and($page1->count())->toBe(12)
        ->and($page1->lastPage())->toBe(3);
});
