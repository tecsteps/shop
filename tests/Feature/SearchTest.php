<?php

use App\Livewire\Storefront\Search\Index;
use App\Livewire\Storefront\Search\Modal;
use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);

    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);

    $theme = Theme::factory()->published()->create([
        'store_id' => $this->store->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
        'title' => 'Main Menu',
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'footer-menu',
        'title' => 'Footer Menu',
    ]);

    app()->instance('current_store', $this->store);
});

// --- SearchService Tests ---

it('indexes and finds a product via FTS5', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Organic Cotton T-Shirt',
        'vendor' => 'EcoWear',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2999,
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product);

    $results = $searchService->search($this->store, 'cotton');

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('returns empty results for non-matching query', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Blue Denim Jacket',
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product);

    $results = $searchService->search($this->store, 'sandals');

    expect($results->total())->toBe(0);
});

it('returns empty results for empty query', function () {
    $searchService = app(SearchService::class);
    $results = $searchService->search($this->store, '');

    expect($results->total())->toBe(0);
});

it('scopes search results to the correct store', function () {
    $otherStore = Store::factory()->create();

    $ourProduct = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Leather Wallet',
    ]);

    $otherProduct = Product::factory()->active()->create([
        'store_id' => $otherStore->id,
        'title' => 'Leather Belt',
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($ourProduct);
    $searchService->syncProduct($otherProduct);

    $results = $searchService->search($this->store, 'leather');

    expect($results->total())->toBe(1)
        ->and($results->first()->id)->toBe($ourProduct->id);
});

it('filters search results by vendor', function () {
    $product1 = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Sneakers',
        'vendor' => 'NikeClone',
    ]);

    $product2 = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Red Boots',
        'vendor' => 'Timberland',
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product1);
    $searchService->syncProduct($product2);

    $results = $searchService->search($this->store, 'red', ['vendor' => 'Timberland']);

    expect($results->total())->toBe(1)
        ->and($results->first()->vendor)->toBe('Timberland');
});

it('autocompletes with prefix matching', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Wireless Headphones',
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product);

    $results = $searchService->autocomplete($this->store, 'wire');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($product->id);
});

it('removes a product from the FTS5 index', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Vintage Watch',
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product);

    expect($searchService->search($this->store, 'vintage')->total())->toBe(1);

    $searchService->removeProduct($product->id);

    expect($searchService->search($this->store, 'vintage')->total())->toBe(0);
});

it('rebuilds the full FTS5 index for a store', function () {
    Product::factory()->active()->count(3)->create([
        'store_id' => $this->store->id,
        'title' => 'Rebuild Test Product',
    ]);

    $searchService = app(SearchService::class);
    $searchService->rebuildIndex($this->store);

    $results = $searchService->search($this->store, 'rebuild');

    expect($results->total())->toBe(3);
});

it('logs search queries', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Analytics Test Product',
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product);
    $searchService->search($this->store, 'analytics');

    $this->assertDatabaseHas('search_queries', [
        'store_id' => $this->store->id,
        'query' => 'analytics',
    ]);
});

// --- SearchSettings Model Tests ---

it('creates search settings for a store', function () {
    $settings = SearchSettings::factory()->create([
        'store_id' => $this->store->id,
        'synonyms_json' => ['shoes' => 'sneakers boots'],
    ]);

    expect($settings->store_id)->toBe($this->store->id)
        ->and($settings->synonyms_json)->toBe(['shoes' => 'sneakers boots']);
});

// --- Storefront Route Tests ---

it('renders the search results page', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/search?q=test');

    $response->assertSuccessful();
});

it('renders the search page without a query', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/search');

    $response->assertSuccessful()
        ->assertSee('Search');
});

// --- Livewire Search Index Tests ---

it('displays search results for matching products', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Premium Yoga Mat',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 3999,
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product);

    Livewire::test(Index::class, ['query' => 'yoga'])
        ->assertSee('Premium Yoga Mat');
});

it('shows no results message for non-matching query', function () {
    Livewire::test(Index::class, ['query' => 'nonexistentxyz'])
        ->assertSee('No results found');
});

it('resets page when query changes', function () {
    Livewire::test(Index::class)
        ->set('query', 'first')
        ->assertSet('query', 'first')
        ->set('query', 'second')
        ->assertSet('query', 'second');
});

it('clears filters', function () {
    Livewire::test(Index::class)
        ->set('vendor', 'TestVendor')
        ->set('minPrice', 10)
        ->set('maxPrice', 100)
        ->call('clearFilters')
        ->assertSet('vendor', null)
        ->assertSet('minPrice', null)
        ->assertSet('maxPrice', null);
});

// --- Livewire Search Modal Tests ---

it('opens and closes the search modal', function () {
    Livewire::test(Modal::class)
        ->assertSet('open', false)
        ->call('openModal')
        ->assertSet('open', true)
        ->call('closeModal')
        ->assertSet('open', false);
});

it('searches products in the modal', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Modal Test Sneakers',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 5999,
    ]);

    $searchService = app(SearchService::class);
    $searchService->syncProduct($product);

    Livewire::test(Modal::class)
        ->call('openModal')
        ->set('query', 'sneakers')
        ->assertSet('hasSearched', true)
        ->assertCount('productResults', 1);
});

it('clears results when query is too short', function () {
    Livewire::test(Modal::class)
        ->call('openModal')
        ->set('query', 'sneakers')
        ->set('query', 'a')
        ->assertSet('hasSearched', false)
        ->assertCount('productResults', 0);
});

it('searches collections in the modal', function () {
    Collection::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Essentials',
        'handle' => 'summer-essentials',
        'status' => 'active',
    ]);

    Livewire::test(Modal::class)
        ->call('openModal')
        ->set('query', 'Summer')
        ->assertCount('collectionResults', 1);
});

it('resets modal state when reopened', function () {
    Livewire::test(Modal::class)
        ->call('openModal')
        ->set('query', 'test search')
        ->call('closeModal')
        ->call('openModal')
        ->assertSet('query', '')
        ->assertSet('hasSearched', false)
        ->assertCount('productResults', 0);
});
