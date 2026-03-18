<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SearchService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(SearchService::class);
});

function createAutocompleteProduct(mixed $store, string $title, array $overrides = []): Product
{
    $product = Product::withoutGlobalScopes()->create(array_merge([
        'store_id' => $store->id,
        'title' => $title,
        'handle' => \Illuminate\Support\Str::slug($title).'-'.uniqid(),
        'status' => ProductStatus::Active,
        'description_html' => '<p>Description</p>',
        'vendor' => 'TestVendor',
        'product_type' => 'TestType',
        'tags' => [],
        'published_at' => now(),
    ], $overrides));

    ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'SKU-'.uniqid(),
        'price_amount' => 1000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
    ]);

    return $product;
}

it('returns autocomplete results for a prefix', function () {
    createAutocompleteProduct($this->store, 'Running Shoes');
    createAutocompleteProduct($this->store, 'Cotton T-Shirt');

    $results = $this->service->autocomplete($this->store, 'Run');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Running Shoes');
});

it('limits autocomplete results', function () {
    for ($i = 1; $i <= 10; $i++) {
        createAutocompleteProduct($this->store, "Limit Widget {$i}");
    }

    $results = $this->service->autocomplete($this->store, 'Widget', 3);

    expect($results)->toHaveCount(3);
});

it('scopes autocomplete results to the current store', function () {
    createAutocompleteProduct($this->store, 'Store1 Sneakers');

    $otherContext = createStoreContext('other-store.test');
    createAutocompleteProduct($otherContext['store'], 'Store2 Sneakers');

    $results = $this->service->autocomplete($this->store, 'Sneakers');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Store1 Sneakers');
});

it('excludes non-active products from autocomplete', function () {
    createAutocompleteProduct($this->store, 'Active Sneakers');
    createAutocompleteProduct($this->store, 'Draft Sneakers', ['status' => ProductStatus::Draft, 'published_at' => null]);

    $results = $this->service->autocomplete($this->store, 'Sneakers');

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Active Sneakers');
});

it('returns empty collection for empty prefix', function () {
    $results = $this->service->autocomplete($this->store, '');

    expect($results)->toBeEmpty();
});

it('returns results with eager loaded relations', function () {
    createAutocompleteProduct($this->store, 'Loaded Product');

    $results = $this->service->autocomplete($this->store, 'Loaded');

    expect($results->first()->relationLoaded('variants'))->toBeTrue()
        ->and($results->first()->relationLoaded('media'))->toBeTrue();
});

it('handles special characters in prefix', function () {
    createAutocompleteProduct($this->store, 'Special Product');

    $results = $this->service->autocomplete($this->store, 'Special"');

    expect($results)->toHaveCount(1);
});
