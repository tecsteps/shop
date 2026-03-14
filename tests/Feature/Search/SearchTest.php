<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\SearchService;

beforeEach(function () {
    $this->store = createStoreContext();
    $this->service = app(SearchService::class);
});

it('finds products by title', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Running Shoes Pro',
    ]);
    $this->service->syncProduct($product);

    $results = $this->service->search($this->store, 'Running');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('finds products by vendor', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Classic Sneaker',
        'vendor' => 'NikeStore',
    ]);
    $this->service->syncProduct($product);

    $results = $this->service->search($this->store, 'NikeStore');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('excludes non-active products from results', function () {
    $draft = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Draft Widget',
        'status' => ProductStatus::Draft,
    ]);
    $active = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Active Widget',
    ]);
    $this->service->syncProduct($draft);
    $this->service->syncProduct($active);

    $results = $this->service->search($this->store, 'Widget');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($active->id);
});

it('scopes search results to the current store', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Store A Product',
    ]);
    $this->service->syncProduct($product);

    $otherStore = createStoreContext();
    $otherProduct = Product::factory()->active()->create([
        'store_id' => $otherStore->id,
        'title' => 'Store B Product',
    ]);
    $this->service->syncProduct($otherProduct);

    app()->instance('current_store', $this->store);

    $results = $this->service->search($this->store, 'Product');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('logs search queries', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Logged Search Item',
    ]);
    $this->service->syncProduct($product);

    $this->service->search($this->store, 'Logged');

    $log = SearchQuery::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->query)->toBe('Logged')
        ->and($log->results_count)->toBe(1);
});
