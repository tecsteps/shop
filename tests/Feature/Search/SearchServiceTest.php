<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function searchServiceStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

test('search service returns matching products scoped to the store and logs the query', function (): void {
    $store = searchServiceStore();
    $otherStore = Store::query()->whereKeyNot($store->getKey())->firstOrFail();

    Product::factory()
        ->for($store)
        ->withDefaultVariant(1999)
        ->create([
            'title' => 'Blue Viscose Search Shirt',
            'handle' => 'blue-viscose-search-shirt',
            'vendor' => 'Search Vendor',
            'tags' => ['viscose', 'blue'],
        ]);

    Product::factory()
        ->for($otherStore)
        ->withDefaultVariant(1999)
        ->create([
            'title' => 'Blue Viscose Search Shirt Deluxe',
            'handle' => 'blue-viscose-search-shirt-deluxe',
            'vendor' => 'Other Vendor',
            'tags' => ['viscose'],
        ]);

    $results = app(SearchService::class)->search($store, 'viscose search', [], 12);

    expect($results->total())->toBe(1)
        ->and($results->getCollection()->first()->title)->toBe('Blue Viscose Search Shirt');

    $queryLog = SearchQuery::withoutGlobalScopes()->where('store_id', $store->getKey())->latest('id')->firstOrFail();

    expect($queryLog->query)->toBe('viscose search')
        ->and($queryLog->results_count)->toBe(1);
});

test('search service keeps the FTS index synchronized through product observer events', function (): void {
    $store = searchServiceStore();

    $product = Product::factory()
        ->for($store)
        ->withDefaultVariant(1599)
        ->create([
            'title' => 'Copper Index Sync Jacket',
            'handle' => 'copper-index-sync-jacket',
        ]);

    expect(app(SearchService::class)->search($store, 'copper sync', [], 12)->total())->toBe(1);

    $product->update(['title' => 'Graphite Index Sync Jacket']);

    expect(app(SearchService::class)->search($store, 'copper sync', [], 12)->total())->toBe(0)
        ->and(app(SearchService::class)->search($store, 'graphite sync', [], 12)->total())->toBe(1);

    $product->delete();

    expect(DB::table('products_fts')->where('product_id', $product->getKey())->exists())->toBeFalse();
});

test('search service paginates unique matches', function (): void {
    $store = searchServiceStore();

    foreach (range(1, 25) as $index) {
        Product::factory()
            ->for($store)
            ->withDefaultVariant(1000 + $index)
            ->create([
                'title' => "Pagination Merino Search {$index}",
                'handle' => "pagination-merino-search-{$index}",
            ]);
    }

    request()->query->set('page', 3);

    $results = app(SearchService::class)->search($store, 'pagination merino', [], 12);

    expect($results->total())->toBe(25)
        ->and($results->currentPage())->toBe(3)
        ->and($results->getCollection())->toHaveCount(1);
});

test('search service expands configured synonyms', function (): void {
    $store = searchServiceStore();

    SearchSettings::withoutGlobalScopes()->updateOrCreate(
        ['store_id' => $store->getKey()],
        [
            'synonyms_json' => [['tee', 'tshirt']],
            'stop_words_json' => [],
        ],
    );

    Product::factory()
        ->for($store)
        ->withDefaultVariant(1999)
        ->create([
            'title' => 'Emerald Tee Synonym Match',
            'handle' => 'emerald-tee-synonym-match',
        ]);

    $results = app(SearchService::class)->search($store, 'tshirt synonym', [], 12);

    expect($results->total())->toBe(1)
        ->and($results->getCollection()->first()->title)->toBe('Emerald Tee Synonym Match');
});

test('search service removes configured stop words before matching', function (): void {
    $store = searchServiceStore();

    SearchSettings::withoutGlobalScopes()->updateOrCreate(
        ['store_id' => $store->getKey()],
        [
            'synonyms_json' => [],
            'stop_words_json' => ['the', 'for'],
        ],
    );

    Product::factory()
        ->for($store)
        ->withDefaultVariant(1999)
        ->create([
            'title' => 'Stopword Linen Token',
            'handle' => 'stopword-linen-token',
        ]);

    $results = app(SearchService::class)->search($store, 'the stopword', [], 12);

    expect($results->total())->toBe(1)
        ->and($results->getCollection()->first()->title)->toBe('Stopword Linen Token');
});
