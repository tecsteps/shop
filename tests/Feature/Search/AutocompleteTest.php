<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(SearchService::class);
});

/**
 * Create an active product with a variant synced to FTS5.
 *
 * @param  array<string, mixed>  $attributes
 */
function createAutocompleteProduct(Store $store, SearchService $service, array $attributes = []): Product
{
    $product = Product::withoutGlobalScopes()->create(array_merge([
        'store_id' => $store->id,
        'title' => 'Test Product',
        'handle' => 'test-product-'.uniqid(),
        'status' => ProductStatus::Active,
        'description_html' => '<p>Test description</p>',
        'vendor' => 'TestVendor',
        'product_type' => 'Clothing',
        'tags' => [],
        'published_at' => now(),
    ], $attributes));

    ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 2999,
        'currency' => 'USD',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);

    $service->syncProduct($product);

    return $product;
}

test('returns suggestions matching prefix', function () {
    createAutocompleteProduct($this->store, $this->service, [
        'title' => 'Summer Dress',
    ]);

    createAutocompleteProduct($this->store, $this->service, [
        'title' => 'Summer Hat',
    ]);

    createAutocompleteProduct($this->store, $this->service, [
        'title' => 'Winter Coat',
    ]);

    $results = $this->service->autocomplete($this->store, 'sum');

    expect($results)->toHaveCount(2);
    expect($results->pluck('title')->toArray())->each->toContain('Summer');
});

test('limits results to configured count', function () {
    for ($i = 1; $i <= 20; $i++) {
        createAutocompleteProduct($this->store, $this->service, [
            'title' => "Sample Item $i",
            'handle' => "sample-item-$i",
        ]);
    }

    $results = $this->service->autocomplete($this->store, 'sample', 5);

    expect($results)->toHaveCount(5);
});

test('returns empty for very short prefix', function () {
    createAutocompleteProduct($this->store, $this->service, [
        'title' => 'Amazing Product',
    ]);

    $results = $this->service->autocomplete($this->store, 'a');

    expect($results)->toHaveCount(0);
});

test('returns empty for no matching prefix', function () {
    createAutocompleteProduct($this->store, $this->service, [
        'title' => 'Summer Dress',
    ]);

    $results = $this->service->autocomplete($this->store, 'xyznotfound');

    expect($results)->toHaveCount(0);
});

test('scopes autocomplete to current store', function () {
    $storeB = Store::factory()->create();

    createAutocompleteProduct($this->store, $this->service, [
        'title' => 'Premium Shoes',
    ]);

    $productB = Product::withoutGlobalScopes()->create([
        'store_id' => $storeB->id,
        'title' => 'Premium Boots',
        'handle' => 'premium-boots',
        'status' => ProductStatus::Active,
        'published_at' => now(),
        'tags' => [],
    ]);
    ProductVariant::create([
        'product_id' => $productB->id,
        'price_amount' => 4999,
        'currency' => 'USD',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);
    $this->service->syncProduct($productB);

    $results = $this->service->autocomplete($this->store, 'premium');

    expect($results)->toHaveCount(1);
    expect($results->first()->store_id)->toBe($this->store->id);
});
