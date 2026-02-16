<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\SearchService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->searchService = app(SearchService::class);
});

it('returns products matching search query', function () {
    $p1 = Product::factory()->active()->for($this->ctx['store'])->create(['title' => 'Blue Cotton T-Shirt']);
    $p2 = Product::factory()->active()->for($this->ctx['store'])->create(['title' => 'Red Wool Sweater']);

    $this->searchService->syncProduct($p1);
    $this->searchService->syncProduct($p2);

    $results = $this->searchService->search($this->ctx['store'], 'cotton');

    expect($results->total())->toBe(1)
        ->and($results->first()->title)->toBe('Blue Cotton T-Shirt');
});

it('scopes search to current store', function () {
    $p1 = Product::factory()->active()->for($this->ctx['store'])->create(['title' => 'T-Shirt']);
    $this->searchService->syncProduct($p1);

    $org2 = \App\Models\Organization::factory()->create();
    $store2 = \App\Models\Store::factory()->for($org2)->create();
    $p2 = Product::factory()->active()->for($store2)->create(['title' => 'T-Shirt Deluxe']);
    $this->searchService->syncProduct($p2);

    $results = $this->searchService->search($this->ctx['store'], 'shirt');

    expect($results->total())->toBe(1);
});

it('returns empty for no matches', function () {
    $results = $this->searchService->search($this->ctx['store'], 'xyznonexistent');
    expect($results->total())->toBe(0);
});

it('logs search query for analytics', function () {
    $this->searchService->search($this->ctx['store'], 'test query');

    expect(SearchQuery::withoutGlobalScopes()->where('query', 'test query')->exists())->toBeTrue();
});
