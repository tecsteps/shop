<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(SearchService::class);
});

function createSearchableProduct(mixed $store, array $overrides = []): Product
{
    $product = Product::factory()->active()->create(array_merge([
        'store_id' => $store->id,
    ], $overrides));

    ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    app(SearchService::class)->syncProduct($product);

    return $product;
}

it('finds products by title', function () {
    $product = createSearchableProduct($this->store, [
        'title' => 'Running Shoes Pro',
    ]);

    $results = $this->service->search($this->store, 'Running');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('finds products by vendor', function () {
    $product = createSearchableProduct($this->store, [
        'title' => 'Classic Sneaker',
        'vendor' => 'NikeStore',
    ]);

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
    ProductVariant::factory()->create(['product_id' => $draft->id]);
    $this->service->syncProduct($draft);

    $active = createSearchableProduct($this->store, [
        'title' => 'Active Widget',
    ]);

    $results = $this->service->search($this->store, 'Widget');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($active->id);
});

it('scopes search results to the current store', function () {
    $product = createSearchableProduct($this->store, [
        'title' => 'Store A Product',
    ]);

    $otherContext = createStoreContext();
    $otherStore = $otherContext['store'];
    app()->instance('current_store', $otherStore);
    createSearchableProduct($otherStore, [
        'title' => 'Store B Product',
    ]);

    app()->instance('current_store', $this->store);

    $results = $this->service->search($this->store, 'Product');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('logs search queries', function () {
    createSearchableProduct($this->store, [
        'title' => 'Logged Search Item',
    ]);

    $this->service->search($this->store, 'Logged');

    $log = SearchQuery::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->query)->toBe('Logged')
        ->and($log->results_count)->toBe(1);
});

it('paginates search results', function () {
    for ($i = 1; $i <= 15; $i++) {
        createSearchableProduct($this->store, [
            'title' => "Searchable Item {$i}",
        ]);
    }

    $results = $this->service->search($this->store, 'searchable', [], 5);

    expect($results->perPage())->toBe(5);
    expect($results->total())->toBe(15);
    expect($results->count())->toBe(5);
});

it('returns empty results for no matches', function () {
    createSearchableProduct($this->store, [
        'title' => 'Leather Wallet',
    ]);

    $results = $this->service->search($this->store, 'xyznonexistent');
    expect($results->total())->toBe(0);
});
