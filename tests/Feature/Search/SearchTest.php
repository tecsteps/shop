<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Create a store, bind it as the current tenant, and return it.
 */
function searchStore(): Store
{
    $store = test()->createStore();
    test()->bindStore($store);

    return $store;
}

/**
 * Create an active, published product with one priced variant.
 */
function searchProduct(Store $store, array $productAttributes = [], array $variantAttributes = []): Product
{
    return Product::factory()
        ->active()
        ->withVariants(1, array_merge(['price_amount' => 2500], $variantAttributes))
        ->create(array_merge(['store_id' => $store->id], $productAttributes));
}

test('syncs a product to the FTS index on create', function () {
    $store = searchStore();

    $product = searchProduct($store, [
        'title' => 'Blue Cotton T-Shirt',
        'description_html' => '<p>Soft <b>organic</b> fabric</p>',
        'vendor' => 'Acme',
        'product_type' => 'Apparel',
        'tags' => ['organic', 'cotton'],
    ]);

    $row = DB::table('products_fts')->where('product_id', $product->id)->sole();

    expect((int) $row->store_id)->toBe($store->id)
        ->and($row->title)->toBe('Blue Cotton T-Shirt')
        ->and($row->description)->toBe('Soft organic fabric')
        ->and($row->vendor)->toBe('Acme')
        ->and($row->product_type)->toBe('Apparel')
        ->and($row->tags)->toBe('organic cotton');
});

test('updates the FTS row when the product changes', function () {
    $store = searchStore();
    $product = searchProduct($store, ['title' => 'Old Title']);

    $product->update(['title' => 'New Shiny Title', 'vendor' => 'New Vendor']);

    $rows = DB::table('products_fts')->where('product_id', $product->id)->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->title)->toBe('New Shiny Title')
        ->and($rows->first()->vendor)->toBe('New Vendor');
});

test('removes the FTS row when the product is deleted', function () {
    $store = searchStore();
    $product = searchProduct($store, ['title' => 'Disposable']);

    expect(DB::table('products_fts')->where('product_id', $product->id)->exists())->toBeTrue();

    $product->delete();

    expect(DB::table('products_fts')->where('product_id', $product->id)->exists())->toBeFalse();
});

test('returns products matching search query', function () {
    $store = searchStore();
    $shirt = searchProduct($store, ['title' => 'Blue Cotton T-Shirt']);
    searchProduct($store, ['title' => 'Red Wool Sweater']);

    $results = app(SearchService::class)->search($store, 'cotton');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($shirt->id);
});

test('finds products by description, vendor, and tags', function () {
    $store = searchStore();
    $byDescription = searchProduct($store, ['title' => 'Plain Shirt', 'description_html' => '<p>Made of merino wool</p>']);
    $byVendor = searchProduct($store, ['title' => 'Plain Pants', 'vendor' => 'Merino House']);
    $byTag = searchProduct($store, ['title' => 'Plain Socks', 'tags' => ['merino']]);

    $search = app(SearchService::class);

    expect($search->search($store, 'merino')->total())->toBe(3);
    expect($search->search($store, 'wool')->items()[0]->id)->toBe($byDescription->id);
    expect($search->search($store, 'Merino House')->items()[0]->id)->toBe($byVendor->id);
    expect($search->search($store, 'merino')->pluck('id'))->toContain($byTag->id);
});

test('matches the last token as a prefix', function () {
    $store = searchStore();
    $shoe = searchProduct($store, ['title' => 'Running Shoe']);

    $results = app(SearchService::class)->search($store, 'running sh');

    expect($results->items()[0]->id)->toBe($shoe->id);
});

test('excludes draft and unpublished products', function () {
    $store = searchStore();
    Product::factory()->withVariants(1)->create(['store_id' => $store->id, 'title' => 'Draft Cotton']);
    Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Unpublished Cotton',
        'status' => \App\Enums\ProductStatus::Active,
        'published_at' => null,
    ]);
    $visible = searchProduct($store, ['title' => 'Visible Cotton']);

    $results = app(SearchService::class)->search($store, 'cotton');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($visible->id);
});

test('scopes search to the current store', function () {
    $storeA = searchStore();
    $storeB = test()->createStore();

    $own = searchProduct($storeA, ['title' => 'T-Shirt']);
    $other = searchProduct($storeB, ['title' => 'T-Shirt Deluxe']);

    $results = app(SearchService::class)->search($storeA, 't-shirt');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($own->id)
        ->and($results->items()[0]->id)->not->toBe($other->id);

    // Other store's rows stay in the index but are never matched.
    expect(DB::table('products_fts')->where('store_id', $storeB->id)->count())->toBe(1);
});

test('returns empty for no matches', function () {
    $store = searchStore();
    searchProduct($store, ['title' => 'Blue Cotton T-Shirt']);

    $results = app(SearchService::class)->search($store, 'xyznonexistent');

    expect($results->total())->toBe(0)
        ->and($results->items())->toBe([]);
});

test('filters by vendor', function () {
    $store = searchStore();
    $acme = searchProduct($store, ['title' => 'Cotton Shirt', 'vendor' => 'Acme Apparel']);
    searchProduct($store, ['title' => 'Cotton Shirt', 'vendor' => 'Other Brand']);

    $results = app(SearchService::class)->search($store, 'cotton', ['vendor' => 'Acme Apparel']);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($acme->id);
});

test('filters by price range', function () {
    $store = searchStore();
    searchProduct($store, ['title' => 'Cheap Cotton'], ['price_amount' => 1000]);
    $expensive = searchProduct($store, ['title' => 'Expensive Cotton'], ['price_amount' => 5000]);

    $results = app(SearchService::class)->search($store, 'cotton', ['price_min' => 2000, 'price_max' => 6000]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($expensive->id);
});

test('filters by collection', function () {
    $store = searchStore();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'title' => 'Summer']);
    $inCollection = searchProduct($store, ['title' => 'Cotton Shirt']);
    searchProduct($store, ['title' => 'Cotton Pants']);
    $collection->products()->attach($inCollection->id);

    $results = app(SearchService::class)->search($store, 'cotton', ['collection_id' => $collection->id]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($inCollection->id);
});

test('filters by stock availability', function () {
    $store = searchStore();
    $inStock = searchProduct($store, ['title' => 'Cotton Shirt']);
    $outOfStock = searchProduct($store, ['title' => 'Cotton Pants']);
    $outOfStock->variants->first()->inventoryItem->update(['quantity_on_hand' => 0, 'quantity_reserved' => 0]);

    $results = app(SearchService::class)->search($store, 'cotton', ['in_stock' => true]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($inStock->id);
});

test('filters by tags', function () {
    $store = searchStore();
    $organic = searchProduct($store, ['title' => 'Cotton Shirt', 'tags' => ['organic', 'cotton']]);
    searchProduct($store, ['title' => 'Cotton Pants', 'tags' => ['cotton']]);

    $results = app(SearchService::class)->search($store, 'cotton', ['tags' => ['organic']]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($organic->id);
});

test('sorts by price ascending and descending', function () {
    $store = searchStore();
    $cheap = searchProduct($store, ['title' => 'Cotton Alpha'], ['price_amount' => 1000]);
    $pricey = searchProduct($store, ['title' => 'Cotton Beta'], ['price_amount' => 9000]);

    $search = app(SearchService::class);

    $asc = $search->search($store, 'cotton', [], 24, 'price_asc');
    $desc = $search->search($store, 'cotton', [], 24, 'price_desc');

    expect($asc->pluck('id')->all())->toBe([$cheap->id, $pricey->id])
        ->and($desc->pluck('id')->all())->toBe([$pricey->id, $cheap->id]);
});

test('sorts by newest first', function () {
    $store = searchStore();
    $older = searchProduct($store, ['title' => 'Cotton Old', 'published_at' => now()->subDays(10)]);
    $newer = searchProduct($store, ['title' => 'Cotton New', 'published_at' => now()->subDay()]);

    $results = app(SearchService::class)->search($store, 'cotton', [], 24, 'newest');

    expect($results->pluck('id')->all())->toBe([$newer->id, $older->id]);
});

test('paginates search results', function () {
    $store = searchStore();

    Product::factory()->active()->withVariants(1)->count(25)->create([
        'store_id' => $store->id,
        'title' => 'Widget',
    ]);

    $search = app(SearchService::class);

    $page1 = $search->search($store, 'widget', [], 12, 'relevance', 1);
    $page2 = $search->search($store, 'widget', [], 12, 'relevance', 2);
    $page3 = $search->search($store, 'widget', [], 12, 'relevance', 3);

    expect($page1->items())->toHaveCount(12)
        ->and($page2->items())->toHaveCount(12)
        ->and($page3->items())->toHaveCount(1)
        ->and($page1->total())->toBe(25)
        ->and($page1->lastPage())->toBe(3);
});

test('logs search query for analytics', function () {
    $store = searchStore();
    searchProduct($store, ['title' => 'Cotton Shirt', 'vendor' => 'Acme']);

    app(SearchService::class)->search($store, 'cotton', ['vendor' => 'Acme']);

    $logged = \App\Models\SearchQuery::query()->sole();

    expect($logged->store_id)->toBe($store->id)
        ->and($logged->query)->toBe('cotton')
        ->and($logged->filters_json)->toBe(['vendor' => 'Acme'])
        ->and($logged->results_count)->toBe(1);
});

test('expands synonyms from search settings', function () {
    $store = searchStore();
    $sneakers = searchProduct($store, ['title' => 'Running Sneakers']);

    SearchSettings::factory()->withSynonyms([['sneakers', 'trainers', 'kicks']])->create(['store_id' => $store->id]);

    $results = app(SearchService::class)->search($store, 'trainers');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($sneakers->id);
});

test('removes stop words from the query', function () {
    $store = searchStore();
    $shirt = searchProduct($store, ['title' => 'Cotton Shirt']);

    SearchSettings::factory()->withStopWords(['the', 'a'])->create(['store_id' => $store->id]);

    $search = app(SearchService::class);

    // Stop word alone would otherwise match nothing/error; it is dropped.
    expect($search->search($store, 'the cotton')->items()[0]->id)->toBe($shirt->id);
    expect($search->search($store, 'the')->total())->toBe(0);
});

test('sanitizes FTS special characters without erroring', function (string $query) {
    $store = searchStore();
    searchProduct($store, ['title' => 'Cotton Shirt']);

    $results = app(SearchService::class)->search($store, $query);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class);
})->with([
    'OR operator' => ['" OR *'],
    'AND NOT operators' => ['cotton AND NOT shirt'],
    'parentheses and quotes' => ['(cotton) "shirt"'],
    'NEAR operator' => ['NEAR(cotton, shirt)'],
    'punctuation' => ['cotton?! @#$%'],
]);

test('reindex rebuilds the store FTS rows', function () {
    $store = searchStore();
    $other = test()->createStore();

    searchProduct($store, ['title' => 'One']);
    searchProduct($store, ['title' => 'Two']);
    searchProduct($other, ['title' => 'Other Store Product']);

    DB::table('products_fts')->delete();
    expect(DB::table('products_fts')->count())->toBe(0);

    $count = app(SearchService::class)->reindex($store);

    expect($count)->toBe(2)
        ->and(DB::table('products_fts')->where('store_id', $store->id)->count())->toBe(2)
        ->and(DB::table('products_fts')->where('store_id', $other->id)->count())->toBe(0);
});

test('search page renders results for the query', function () {
    $store = searchStore();
    searchProduct($store, ['title' => 'Blue Cotton T-Shirt']);

    $this->get('http://'.$store->handle.'.test/search?q=cotton')
        ->assertOk()
        ->assertSee('Blue Cotton T-Shirt')
        ->assertSee('1 result for');
});

test('search page shows the empty state for a query without matches', function () {
    $store = searchStore();

    $this->get('http://'.$store->handle.'.test/search?q=zzzunknown')
        ->assertOk()
        ->assertSee('No results found for');
});

test('search modal renders matching suggestions', function () {
    $store = searchStore();
    searchProduct($store, ['title' => 'Summer Dress']);

    \Livewire\Livewire::test(\App\Livewire\Storefront\Search\Modal::class)
        ->set('query', 'sum')
        ->assertSee('Summer Dress')
        ->assertSee('View all results');
});
