<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\SearchService;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->searchService = app(SearchService::class);
});

function createAutocompleteProduct(Store $store, array $overrides = []): Product
{
    $product = Product::withoutEvents(function () use ($store, $overrides) {
        return Product::factory()->create(array_merge([
            'store_id' => $store->id,
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ], $overrides));
    });

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'is_default' => true,
    ]);

    app(SearchService::class)->syncProduct($product);

    return $product;
}

it('returns autocomplete results matching a prefix', function () {
    createAutocompleteProduct($this->store, ['title' => 'Running Shoes']);
    createAutocompleteProduct($this->store, ['title' => 'Running Shorts']);
    createAutocompleteProduct($this->store, ['title' => 'Hiking Boots']);

    $results = $this->searchService->autocomplete($this->store, 'Runn');

    expect($results)->toHaveCount(2);
});

it('returns empty collection for empty prefix', function () {
    createAutocompleteProduct($this->store, ['title' => 'Something']);

    $results = $this->searchService->autocomplete($this->store, '');

    expect($results)->toBeEmpty();
});

it('limits autocomplete results', function () {
    for ($i = 1; $i <= 10; $i++) {
        createAutocompleteProduct($this->store, ['title' => "Blue Widget {$i}"]);
    }

    $results = $this->searchService->autocomplete($this->store, 'Blue', 3);

    expect($results)->toHaveCount(3);
});

it('scopes autocomplete to the given store', function () {
    $otherStore = Store::factory()->create();

    createAutocompleteProduct($this->store, ['title' => 'Exclusive Shirt']);
    createAutocompleteProduct($otherStore, ['title' => 'Exclusive Pants']);

    $results = $this->searchService->autocomplete($this->store, 'Exclusive');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Exclusive Shirt');
});

it('only returns active products in autocomplete', function () {
    createAutocompleteProduct($this->store, ['title' => 'Active Hat']);

    $draft = Product::withoutEvents(function () {
        return Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => 'Draft Hat',
            'status' => ProductStatus::Draft,
        ]);
    });
    $this->searchService->syncProduct($draft);

    $results = $this->searchService->autocomplete($this->store, 'Hat');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Active Hat');
});

it('returns product id, title, and handle', function () {
    $product = createAutocompleteProduct($this->store, ['title' => 'Test Item', 'handle' => 'test-item']);

    $results = $this->searchService->autocomplete($this->store, 'Test');

    $first = $results->first();
    expect($first->id)->toBe($product->id)
        ->and($first->title)->toBe('Test Item')
        ->and($first->handle)->toBe('test-item');
});
