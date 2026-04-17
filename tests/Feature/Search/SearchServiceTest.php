<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\SearchService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeActiveProduct(Store $store, string $title, array $overrides = []): Product
{
    return Product::factory()->create(array_merge([
        'store_id' => $store->getKey(),
        'title' => $title,
        'status' => ProductStatus::Active->value,
        'published_at' => now(),
    ], $overrides));
}

it('matches products by title', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Running Sneakers');
    makeActiveProduct($store, 'Winter Coat');

    $results = app(SearchService::class)->search($store, 'running', [], null, false);

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Running Sneakers');
});

it('is case-insensitive', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Leather Wallet');

    $results = app(SearchService::class)->search($store, 'LEATHER', [], null, false);

    expect($results)->toHaveCount(1);
});

it('supports prefix search on the last token', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Running Sneakers');

    $results = app(SearchService::class)->search($store, 'running sne', [], null, false);

    expect($results)->toHaveCount(1);
});

it('returns empty when there are no matches', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Running Sneakers');

    $results = app(SearchService::class)->search($store, 'zzz-nothing', [], null, false);

    expect($results)->toBeEmpty();
});

it('returns empty for an empty query', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Running Sneakers');

    $results = app(SearchService::class)->search($store, '   ', [], null, false);

    expect($results)->toBeEmpty();
});

it('scopes results to the given store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    makeActiveProduct($storeA, 'Shared Keyword A');
    makeActiveProduct($storeB, 'Shared Keyword B');

    $results = app(SearchService::class)->search($storeA, 'shared', [], null, false);

    expect($results)->toHaveCount(1)
        ->and($results->first()->store_id)->toBe($storeA->getKey());
});

it('excludes non-active or unpublished products', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Live Shoe');
    Product::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Draft Shoe',
        'status' => ProductStatus::Draft->value,
        'published_at' => null,
    ]);
    Product::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Archived Shoe',
        'status' => ProductStatus::Archived->value,
        'published_at' => now(),
    ]);

    $results = app(SearchService::class)->search($store, 'shoe', [], null, false);

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Live Shoe');
});

it('logs a search query record with result count', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Blue Mug');

    app(SearchService::class)->search($store, 'mug', [], 'sess-abc');

    $row = SearchQuery::query()->withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();

    expect($row->query)->toBe('mug')
        ->and($row->results_count)->toBe(1)
        ->and($row->session_id)->toBe('sess-abc');
});

it('sanitises FTS5 special characters', function () {
    $store = Store::factory()->create();
    makeActiveProduct($store, 'Coffee Beans');

    $results = app(SearchService::class)->search($store, 'coffee*) OR "', [], null, false);

    expect($results)->toHaveCount(1);
});
