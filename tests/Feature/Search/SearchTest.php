<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\SearchService;

it('returns products matching search query', function () {
    $ctx = createStoreContext();
    Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'Blue Cotton T-Shirt']);
    Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'Red Wool Sweater']);

    $results = app(SearchService::class)->search($ctx['store'], 'cotton');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('Blue Cotton T-Shirt');
});

it('scopes search to current store', function () {
    $ctx = createStoreContext();
    $other = \App\Models\Store::factory()->create();
    Product::factory()->active()->create(['store_id' => $ctx['store']->id, 'title' => 'T-Shirt']);
    Product::factory()->active()->create(['store_id' => $other->id, 'title' => 'T-Shirt Deluxe']);

    $results = app(SearchService::class)->search($ctx['store'], 't-shirt');

    expect($results->total())->toBe(1);
});

it('returns empty for no matches', function () {
    $ctx = createStoreContext();

    $results = app(SearchService::class)->search($ctx['store'], 'xyznonexistent');

    expect($results->total())->toBe(0);
});

it('logs search query for analytics', function () {
    $ctx = createStoreContext();

    app(SearchService::class)->search($ctx['store'], 'cotton');

    expect(SearchQuery::where('store_id', $ctx['store']->id)->where('query', 'cotton')->exists())->toBeTrue();
});
