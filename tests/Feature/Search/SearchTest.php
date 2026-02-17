<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\SearchService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $ctx = createStoreContext();
    $this->store = $ctx['store'];
    $this->user = $ctx['user'];
    $this->service = app(SearchService::class);

    DB::statement('CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(product_id, store_id, title, description, vendor, product_type, tags)');
});

test('returns products matching search query', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Organic Cotton T-Shirt',
        'vendor' => 'EcoWear',
    ]);
    $this->service->syncProduct($product);

    $results = $this->service->search($this->store, 'Cotton');

    expect($results->total())->toBe(1)
        ->and($results->first()->title)->toBe('Organic Cotton T-Shirt');
});

test('scopes search to current store', function () {
    $otherCtx = createStoreContext('other-store.test');
    $otherStore = $otherCtx['store'];

    $ownProduct = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Denim Jacket',
    ]);
    $this->service->syncProduct($ownProduct);

    $otherProduct = Product::factory()->active()->create([
        'store_id' => $otherStore->id,
        'title' => 'Blue Denim Pants',
    ]);
    $this->service->syncProduct($otherProduct);

    $results = $this->service->search($this->store, 'Blue Denim');

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($ownProduct->id);
});

test('returns empty for no matches', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Wool Sweater',
    ]);
    $this->service->syncProduct($product);

    $results = $this->service->search($this->store, 'nonexistent-xyz-term');

    expect($results->total())->toBe(0);
});

test('logs search query for analytics', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Running Shoes',
    ]);
    $this->service->syncProduct($product);

    $this->service->search($this->store, 'Running');

    $logged = SearchQuery::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($logged)->not->toBeNull()
        ->and($logged->query)->toBe('Running')
        ->and($logged->results_count)->toBe(1);
});

test('paginates search results', function () {
    foreach (range(1, 15) as $i) {
        $product = Product::factory()->active()->create([
            'store_id' => $this->store->id,
            'title' => "Widget Model {$i}",
        ]);
        $this->service->syncProduct($product);
    }

    $page1 = $this->service->search($this->store, 'Widget', perPage: 5);

    expect($page1->total())->toBe(15)
        ->and($page1->perPage())->toBe(5)
        ->and($page1->count())->toBe(5)
        ->and($page1->lastPage())->toBe(3);
});
