<?php

use App\Models\Product;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(SearchService::class);
});

it('returns matching products for a search query', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Running Shoes',
        'vendor' => 'Nike',
    ]);
    $this->service->syncProduct($product);

    $other = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Hiking Boots',
        'vendor' => 'Adidas',
    ]);
    $this->service->syncProduct($other);

    $results = $this->service->search($this->store, 'running');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($product->id);
});

it('scopes search results to the current store', function () {
    $storeA = $this->store;
    $productA = Product::factory()->active()->create([
        'store_id' => $storeA->id,
        'title' => 'Laptop Stand',
    ]);
    $this->service->syncProduct($productA);

    $storeB = \App\Models\Store::factory()->create();
    $productB = Product::factory()->active()->create([
        'store_id' => $storeB->id,
        'title' => 'Laptop Bag',
    ]);
    $this->service->syncProduct($productB);

    $results = $this->service->search($storeA, 'laptop');

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($productA->id);
});

it('returns empty results when nothing matches', function () {
    Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Running Shoes',
    ]);

    $results = $this->service->search($this->store, 'xyznonexistent');

    expect($results->total())->toBe(0);
});

it('logs the search query with result count', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Organic Coffee Beans',
    ]);
    $this->service->syncProduct($product);

    $this->service->search($this->store, 'coffee');

    $log = \App\Models\SearchQuery::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->query)->toBe('coffee')
        ->and($log->results_count)->toBe(1);
});

it('paginates search results', function () {
    foreach (range(1, 30) as $i) {
        $product = Product::factory()->active()->create([
            'store_id' => $this->store->id,
            'title' => "Widget Model {$i}",
        ]);
        $this->service->syncProduct($product);
    }

    $results = $this->service->search($this->store, 'widget', [], 12);

    expect($results)->toHaveCount(12)
        ->and($results->total())->toBe(30);
});
