<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->search = app(SearchService::class);
});

it('returns products matching search query', function () {
    Product::factory()->active()->for($this->store)->create(['title' => 'Blue Cotton T-Shirt']);
    Product::factory()->active()->for($this->store)->create(['title' => 'Red Wool Sweater']);

    $results = $this->search->search($this->store, 'cotton');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Blue Cotton T-Shirt');
});

it('scopes search to current store', function () {
    Product::factory()->active()->for($this->store)->create(['title' => 'T-Shirt']);

    $otherContext = createStoreContext();
    Product::factory()->active()->for($otherContext['store'])->create(['title' => 'T-Shirt Deluxe']);

    app()->instance('current_store', $this->store);

    $results = $this->search->search($this->store, 't-shirt');

    expect($results->total())->toBe(1);
    expect($results->first()->store_id)->toBe($this->store->getKey());
    expect($results->first()->title)->toBe('T-Shirt');
});

it('returns empty for no matches', function () {
    Product::factory()->active()->for($this->store)->create(['title' => 'Blue Cotton T-Shirt']);

    $results = $this->search->search($this->store, 'xyznonexistent');

    expect($results->total())->toBe(0);
    expect($results->items())->toBe([]);
});

it('logs search query for analytics', function () {
    Product::factory()->active()->for($this->store)->create(['title' => 'Blue Cotton T-Shirt']);

    $this->search->search($this->store, 'cotton');

    $logged = SearchQuery::query()->where('query', 'cotton')->first();

    expect($logged)->not->toBeNull();
    expect($logged->store_id)->toBe($this->store->getKey());
    expect($logged->results_count)->toBe(1);
});

it('paginates search results', function () {
    foreach (range(1, 25) as $index) {
        Product::factory()->active()->for($this->store)->create(['title' => "Cotton Shirt {$index}"]);
    }

    $pageOne = $this->search->search($this->store, 'cotton', [], 12, 'relevance', 'page', 1);
    $pageTwo = $this->search->search($this->store, 'cotton', [], 12, 'relevance', 'page', 2);
    $pageThree = $this->search->search($this->store, 'cotton', [], 12, 'relevance', 'page', 3);

    expect($pageOne->total())->toBe(25);
    expect($pageOne->lastPage())->toBe(3);
    expect($pageOne->count())->toBe(12);
    expect($pageTwo->count())->toBe(12);
    expect($pageThree->count())->toBe(1);
});
