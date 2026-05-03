<?php

use App\Livewire\Admin\Search\Settings as AdminSearchSettings;
use App\Livewire\Storefront\Search\Index as StorefrontSearchIndex;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
});

test('search service uses the FTS index and logs storefront queries', function (): void {
    $products = app(SearchService::class)->search($this->store, 'linen', [], 12);

    expect($products->total())->toBe(1)
        ->and($products->getCollection()->first()->title)->toBe('Linen Shirt');

    $query = SearchQuery::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->latest('id')
        ->firstOrFail();

    expect($query->query)->toBe('linen')
        ->and($query->results_count)->toBe(1);
});

test('search applies synonyms and only returns published active products', function (): void {
    Product::factory()
        ->for($this->store)
        ->draft()
        ->create([
            'title' => 'Hidden Trousers',
            'handle' => 'hidden-trousers',
            'vendor' => 'Acme Apparel',
            'product_type' => 'Pants',
        ]);

    $synonymResults = app(SearchService::class)->search($this->store, 'tshirt', [], 12);
    $hiddenResults = app(SearchService::class)->search($this->store, 'hidden', [], 12);

    expect($synonymResults->getCollection()->pluck('title')->all())->toContain('Logo Tee')
        ->and($hiddenResults->total())->toBe(0);
});

test('product observer keeps the FTS table in sync', function (): void {
    $product = Product::factory()
        ->for($this->store)
        ->create([
            'title' => 'Canvas Bucket Bag',
            'handle' => 'canvas-bucket-bag',
            'vendor' => 'Acme Bags',
            'product_type' => 'Accessories',
        ]);

    ProductVariant::factory()
        ->default()
        ->for($product)
        ->create([
            'price_amount' => 3900,
            'currency' => $this->store->default_currency,
        ]);

    expect(app(SearchService::class)->search($this->store, 'bucket', [], 12)->total())->toBe(1);

    $product->update(['title' => 'Waxed Field Bag']);

    expect(app(SearchService::class)->search($this->store, 'bucket', [], 12)->total())->toBe(0)
        ->and(app(SearchService::class)->search($this->store, 'waxed', [], 12)->total())->toBe(1);

    $product->delete();

    expect(app(SearchService::class)->search($this->store, 'waxed', [], 12)->total())->toBe(0);
});

test('storefront search api returns results facets suggestions and pagination metadata', function (): void {
    $this->getJson('http://shop.test/api/storefront/v1/search?q=linen')
        ->assertOk()
        ->assertJsonPath('query', 'linen')
        ->assertJsonPath('results.0.title', 'Linen Shirt')
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('facets.vendors.0.value', 'Acme Apparel');

    $this->getJson('http://shop.test/api/storefront/v1/search/suggest?q=lin')
        ->assertOk()
        ->assertJsonPath('suggestions.0.title', 'Linen Shirt')
        ->assertJsonPath('suggestions.0.type', 'product');
});

test('storefront search page and admin settings are livewire backed', function (): void {
    app()->instance('current_store', $this->store);

    Livewire::test(StorefrontSearchIndex::class)
        ->set('q', 'linen')
        ->assertSee('Linen Shirt')
        ->set('vendor', 'Acme Apparel')
        ->assertSee('Linen Shirt');

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $this->actingAs($user);
    session(['current_store_id' => $this->store->id]);

    Livewire::test(AdminSearchSettings::class)
        ->set('synonymGroups', "bag, tote\njacket, coat")
        ->set('stopWords', "foo\nbar")
        ->call('save')
        ->assertHasNoErrors()
        ->call('reindex')
        ->assertHasNoErrors();

    $settings = SearchSettings::query()->where('store_id', $this->store->id)->firstOrFail();

    expect($settings->synonyms_json)->toBe([
        ['bag', 'tote'],
        ['jacket', 'coat'],
    ])->and($settings->stop_words_json)->toBe(['foo', 'bar']);
});
