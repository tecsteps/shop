<?php

use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function storefrontSearchApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

test('storefront search api returns store scoped product results with facets', function (): void {
    $store = storefrontSearchApiStore();
    $otherStore = Store::query()->whereKeyNot($store->getKey())->firstOrFail();

    Product::factory()
        ->for($store)
        ->withDefaultVariant(2199)
        ->create([
            'title' => 'Cloud Cotton Search Tee',
            'handle' => 'cloud-cotton-search-tee',
            'vendor' => 'Search Vendor',
            'product_type' => 'T-Shirts',
            'tags' => ['cotton', 'cloud'],
        ]);

    Product::factory()
        ->for($otherStore)
        ->withDefaultVariant(2199)
        ->create([
            'title' => 'Cloud Cotton Search Tee Other Store',
            'handle' => 'cloud-cotton-search-tee-other-store',
            'vendor' => 'Other Vendor',
            'product_type' => 'T-Shirts',
            'tags' => ['cotton'],
        ]);

    $filters = urlencode(json_encode([
        'vendor' => 'Search Vendor',
        'price_min' => 1500,
        'price_max' => 2500,
        'in_stock' => true,
    ], JSON_THROW_ON_ERROR));

    $this->withHeader('Host', 'shop.test')
        ->getJson("/api/storefront/v1/search?q=cloud%20cotton&filters={$filters}&sort=price_asc")
        ->assertOk()
        ->assertJsonPath('query', 'cloud cotton')
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('results.0.title', 'Cloud Cotton Search Tee')
        ->assertJsonPath('results.0.price_amount', 2199)
        ->assertJsonPath('facets.vendors.0.value', 'Search Vendor')
        ->assertJsonPath('facets.price_range.min', 2199);

    expect(SearchQuery::withoutGlobalScopes()->where('store_id', $store->getKey())->where('query', 'cloud cotton')->exists())->toBeTrue();
});

test('storefront search api returns empty results for no matches and validates input', function (): void {
    $this->withHeader('Host', 'shop.test')
        ->getJson('/api/storefront/v1/search?q=xyznonexistent')
        ->assertOk()
        ->assertJsonPath('pagination.total', 0)
        ->assertJsonPath('results', []);

    $this->withHeader('Host', 'shop.test')
        ->getJson('/api/storefront/v1/search')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');

    $this->withHeader('Host', 'shop.test')
        ->getJson('/api/storefront/v1/search?q=test&filters=not-json')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('filters');
});

test('storefront search api paginates results', function (): void {
    $store = storefrontSearchApiStore();

    foreach (range(1, 25) as $index) {
        Product::factory()
            ->for($store)
            ->withDefaultVariant(1200 + $index)
            ->create([
                'title' => "Api Pagination Linen Search {$index}",
                'handle' => "api-pagination-linen-search-{$index}",
            ]);
    }

    $this->withHeader('Host', 'shop.test')
        ->getJson('/api/storefront/v1/search?q=api%20pagination%20linen&per_page=12&page=2')
        ->assertOk()
        ->assertJsonPath('pagination.total', 25)
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.last_page', 3)
        ->assertJsonCount(12, 'results');
});

test('storefront search suggest api returns product and collection suggestions', function (): void {
    $store = storefrontSearchApiStore();

    Product::factory()
        ->for($store)
        ->withDefaultVariant(1899)
        ->create([
            'title' => 'Alpaca Suggest Search Jacket',
            'handle' => 'alpaca-suggest-search-jacket',
        ]);

    ProductCollection::factory()->for($store)->create([
        'title' => 'Alpaca Search Collection',
        'handle' => 'alpaca-search-collection',
        'status' => 'active',
    ]);

    $response = $this->withHeader('Host', 'shop.test')
        ->getJson('/api/storefront/v1/search/suggest?q=alpaca&limit=5')
        ->assertOk()
        ->assertJsonPath('query', 'alpaca');

    expect(collect($response['suggestions'])->pluck('type')->all())->toContain('product', 'collection');
});
