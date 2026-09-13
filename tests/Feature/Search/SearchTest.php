<?php

use App\Models\Collection;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\ProductService;
use Tests\Support\CatalogTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createSearchProduct(array $overrides = [], array $variantOverrides = []): \App\Models\Product
{
    $payload = CatalogTestHelper::productPayload($overrides);
    $payload['status'] = $overrides['status'] ?? 'active';
    $payload['variants'][0] = [...$payload['variants'][0], ...$variantOverrides];

    return app(ProductService::class)->create(app(\App\Tenancy\CurrentStore::class)->get(), $payload);
}

beforeEach(function () {
    ['store' => $store, 'domain' => $domain, 'user' => $user] = $this->createStoreContext();
    $this->searchStore = $store;
    $this->searchDomain = $domain;
    $this->searchUser = $user;
});

function storefrontSearchUrl(string $hostname, string $path): string
{
    return "http://{$hostname}{$path}";
}

it('returns products matching the search query', function () {
    createSearchProduct(['title' => 'Blue Cotton T-Shirt', 'tags' => ['cotton']], ['sku' => 'COT-1']);
    createSearchProduct(['title' => 'Red Wool Sweater', 'tags' => ['wool'], 'description_html' => '<p>A warm wool sweater.</p>'], ['sku' => 'WOL-1']);

    $response = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search?q=cotton'));

    $response->assertOk();
    $response->assertJsonPath('query', 'cotton');
    $titles = collect($response->json('results'))->pluck('title')->all();
    expect($titles)->toContain('Blue Cotton T-Shirt');
    expect($titles)->not->toContain('Red Wool Sweater');
    $response->assertJsonStructure(['query', 'results' => [[
        'id', 'title', 'handle', 'vendor', 'product_type', 'price_amount', 'compare_at_amount',
        'currency', 'image_url', 'in_stock', 'tags',
    ]], 'facets' => ['vendors', 'tags', 'price_range'], 'pagination' => [
        'current_page', 'per_page', 'total', 'last_page',
    ]]);
});

it('ranks multi-token matches higher', function () {
    createSearchProduct(['title' => 'Cotton Shirt', 'description_html' => '<p>Crisp and cool.</p>', 'tags' => []], ['sku' => 'CS-1']);
    createSearchProduct(['title' => 'Cotton Pants', 'description_html' => '<p>Relaxed fit.</p>', 'tags' => []], ['sku' => 'CP-1']);

    $response = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search?q=cotton%20shirt'));

    $response->assertOk();
    expect($response->json('pagination.total'))->toBe(1);
    $response->assertJsonPath('results.0.title', 'Cotton Shirt');
});

it('matches description vendor and tags', function () {
    createSearchProduct(['title' => 'Plain Mug', 'description_html' => '<p>Handmade ceramic mug.</p>', 'vendor' => 'Kiln Works', 'tags' => ['ceramic']], ['sku' => 'MUG-1']);

    $hostname = $this->searchDomain->hostname;
    expect($this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=ceramic'))->json('pagination.total'))->toBe(1);
    expect($this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=kiln'))->json('pagination.total'))->toBe(1);
    expect($this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=mug'))->json('pagination.total'))->toBe(1);
});

it('scopes search to the current store', function () {
    createSearchProduct(['title' => 'T-Shirt'], ['sku' => 'TS-A']);

    ['store' => $storeB, 'domain' => $domainB] = $this->createStoreContext();
    app(\App\Tenancy\CurrentStore::class)->set($storeB);
    app(ProductService::class)->create($storeB, [...CatalogTestHelper::productPayload(['title' => 'T-Shirt Deluxe', 'status' => 'active'])]);

    $responseA = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search?q=t-shirt'));
    $responseB = $this->getJson(storefrontSearchUrl($domainB->hostname, '/api/storefront/v1/search?q=t-shirt'));

    expect(collect($responseA->json('results'))->pluck('title')->all())->toBe(['T-Shirt']);
    expect(collect($responseB->json('results'))->pluck('title')->all())->toBe(['T-Shirt Deluxe']);
});

it('returns empty results for no matches', function () {
    createSearchProduct(['title' => 'Blue Cotton T-Shirt'], ['sku' => 'COT-1']);

    $response = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search?q=xyznonexistent'));

    $response->assertOk();
    expect($response->json('results'))->toBe([]);
    $response->assertJsonPath('pagination.total', 0);
    $response->assertJsonPath('facets.price_range', ['min' => 0, 'max' => 0]);
});

it('excludes inactive and unpublished products', function () {
    createSearchProduct(['title' => 'Hidden Draft', 'status' => 'draft'], ['sku' => 'H-1']);
    $archived = createSearchProduct(['title' => 'Hidden Archived'], ['sku' => 'H-2']);
    app(ProductService::class)->transitionStatus($archived->fresh(), \App\Enums\ProductStatus::Archived);

    $response = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search?q=hidden'));

    $response->assertOk();
    $response->assertJsonPath('pagination.total', 0);
});

it('logs search queries for analytics', function () {
    createSearchProduct(['title' => 'Blue Cotton T-Shirt'], ['sku' => 'COT-1']);

    $filters = urlencode(json_encode(['vendor' => 'Acme Apparel']));
    $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, "/api/storefront/v1/search?q=cotton&filters={$filters}"))->assertOk();

    $logged = SearchQuery::query()->where('store_id', $this->searchStore->id)->firstOrFail();
    expect($logged->query)->toBe('cotton');
    expect($logged->results_count)->toBe(1);
    expect($logged->filters_json)->toBe(['vendor' => 'Acme Apparel']);
});

it('paginates search results', function () {
    foreach (range(1, 26) as $index) {
        createSearchProduct(['title' => "Bulk Item {$index}"], ['sku' => "BULK-{$index}"]);
    }

    $hostname = $this->searchDomain->hostname;
    $page1 = $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=bulk&per_page=12&page=1'));
    $page2 = $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=bulk&per_page=12&page=2'));
    $page3 = $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=bulk&per_page=12&page=3'));

    expect($page1->json('results'))->toHaveCount(12);
    expect($page2->json('results'))->toHaveCount(12);
    expect($page3->json('results'))->toHaveCount(2);
    $page1->assertJsonPath('pagination.total', 26);
    $page1->assertJsonPath('pagination.last_page', 3);
});

it('filters search results', function () {
    $collection = Collection::factory()->create(['store_id' => $this->searchStore->id]);

    $cheap = createSearchProduct(['title' => 'Cheap Cotton Tee', 'tags' => ['sale'], 'collections' => [$collection->id]], ['sku' => 'F-1', 'price_amount' => 1000, 'compare_at_amount' => null]);
    createSearchProduct(['title' => 'Expensive Cotton Tee', 'tags' => ['luxury']], ['sku' => 'F-2', 'price_amount' => 9000, 'compare_at_amount' => null]);
    createSearchProduct(['title' => 'Sold Out Cotton Tee'], ['sku' => 'F-3', 'price_amount' => 2000, 'compare_at_amount' => null, 'inventory' => ['quantity_on_hand' => 0, 'policy' => 'deny']]);
    createSearchProduct(['title' => 'Backorder Cotton Tee', 'vendor' => 'Other Vendor'], ['sku' => 'F-4', 'price_amount' => 2000, 'compare_at_amount' => null, 'inventory' => ['quantity_on_hand' => 0, 'policy' => 'continue']]);

    $hostname = $this->searchDomain->hostname;
    $search = fn (array $filters): array => $this->getJson(
        storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=cotton&filters='.urlencode(json_encode($filters)))
    )->json('results');

    expect(collect($search(['collection_id' => $collection->id]))->pluck('id')->all())->toBe([$cheap->id]);
    expect(collect($search(['price_min' => 5000]))->pluck('title')->all())->toBe(['Expensive Cotton Tee']);
    expect(collect($search(['price_max' => 1500]))->pluck('title')->all())->toBe(['Cheap Cotton Tee']);

    $inStock = collect($search(['in_stock' => true]))->pluck('title')->all();
    expect($inStock)->toContain('Cheap Cotton Tee', 'Backorder Cotton Tee');
    expect($inStock)->not->toContain('Sold Out Cotton Tee');

    expect(collect($search(['tags' => ['luxury']]))->pluck('title')->all())->toBe(['Expensive Cotton Tee']);
    expect(collect($search(['vendor' => 'other vendor']))->pluck('title')->all())->toBe(['Backorder Cotton Tee']);
});

it('sorts search results', function () {
    $first = createSearchProduct(['title' => 'Sortable Alpha'], ['sku' => 'S-1', 'price_amount' => 3000, 'compare_at_amount' => null]);
    $second = createSearchProduct(['title' => 'Sortable Beta'], ['sku' => 'S-2', 'price_amount' => 1000, 'compare_at_amount' => null]);
    $third = createSearchProduct(['title' => 'Sortable Gamma'], ['sku' => 'S-3', 'price_amount' => 2000, 'compare_at_amount' => null]);

    Product::query()->whereKey($first->id)->update(['created_at' => now()->subDays(3)]);
    Product::query()->whereKey($second->id)->update(['created_at' => now()->subDays(2)]);
    Product::query()->whereKey($third->id)->update(['created_at' => now()->subDay()]);

    OrderLine::factory()->create(['product_id' => $second->id, 'variant_id' => $second->productVariants()->first()->id, 'quantity' => 10]);
    OrderLine::factory()->create(['product_id' => $third->id, 'variant_id' => $third->productVariants()->first()->id, 'quantity' => 3]);

    $hostname = $this->searchDomain->hostname;
    $titles = fn (string $sort): array => collect($this->getJson(
        storefrontSearchUrl($hostname, "/api/storefront/v1/search?q=sortable&sort={$sort}")
    )->json('results'))->pluck('title')->all();

    expect($titles('price_asc'))->toBe(['Sortable Beta', 'Sortable Gamma', 'Sortable Alpha']);
    expect($titles('price_desc'))->toBe(['Sortable Alpha', 'Sortable Gamma', 'Sortable Beta']);
    expect($titles('newest'))->toBe(['Sortable Gamma', 'Sortable Beta', 'Sortable Alpha']);
    expect($titles('best_selling'))->toBe(['Sortable Beta', 'Sortable Gamma', 'Sortable Alpha']);
});

it('returns search facets', function () {
    createSearchProduct(['title' => 'Faceted Tee', 'tags' => ['cotton', 'sale']], ['sku' => 'FA-1', 'price_amount' => 1500, 'compare_at_amount' => null]);
    createSearchProduct(['title' => 'Faceted Hat', 'tags' => ['cotton']], ['sku' => 'FA-2', 'price_amount' => 4500, 'compare_at_amount' => null]);

    $response = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search?q=faceted'));

    $response->assertOk();
    $response->assertJsonPath('facets.vendors', [['value' => 'Acme Apparel', 'count' => 2]]);
    $response->assertJsonPath('facets.price_range', ['min' => 1500, 'max' => 4500]);

    $tags = collect($response->json('facets.tags'))->pluck('count', 'value')->all();
    expect($tags)->toBe(['cotton' => 2, 'sale' => 1]);
});

it('validates search requests', function () {
    $hostname = $this->searchDomain->hostname;

    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search'))->assertUnprocessable();
    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q='.str_repeat('a', 201)))->assertUnprocessable();
    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=tee&sort=bogus'))->assertUnprocessable();
    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=tee&filters=not-json'))->assertUnprocessable();
    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=tee&per_page=99'))->assertUnprocessable();
});

it('returns 404 for unknown storefront hostnames', function () {
    $this->getJson(storefrontSearchUrl('nonexistent.test', '/api/storefront/v1/search?q=tee'))->assertNotFound();
});

it('suggests products and collections by prefix', function () {
    createSearchProduct(['title' => 'Summer Dress'], ['sku' => 'SU-1']);
    createSearchProduct(['title' => 'Summer Hat'], ['sku' => 'SU-2']);
    createSearchProduct(['title' => 'Winter Coat'], ['sku' => 'WI-1']);
    Collection::factory()->create(['store_id' => $this->searchStore->id, 'title' => 'Summer Sale']);

    $response = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search/suggest?q=sum&limit=10'));

    $response->assertOk();
    $response->assertJsonPath('query', 'sum');

    $suggestions = $response->json('suggestions');
    $titles = collect($suggestions)->pluck('title')->all();
    expect($titles)->toContain('Summer Dress', 'Summer Hat', 'Summer Sale');
    expect($titles)->not->toContain('Winter Coat');

    $product = collect($suggestions)->firstWhere('type', 'product');
    expect($product)->toHaveKeys(['type', 'title', 'handle', 'image_url', 'price_amount', 'currency']);

    $collection = collect($suggestions)->firstWhere('type', 'collection');
    expect($collection)->toHaveKeys(['type', 'title', 'handle', 'image_url']);
});

it('limits and scopes suggestions', function () {
    foreach (range(1, 8) as $index) {
        createSearchProduct(['title' => "Sunglasses Model {$index}"], ['sku' => "SG-{$index}"]);
    }

    ['domain' => $domainB] = $this->createStoreContext();

    $response = $this->getJson(storefrontSearchUrl($this->searchDomain->hostname, '/api/storefront/v1/search/suggest?q=sun&limit=5'));
    expect($response->json('suggestions'))->toHaveCount(5);

    $responseB = $this->getJson(storefrontSearchUrl($domainB->hostname, '/api/storefront/v1/search/suggest?q=sun'));
    expect($responseB->json('suggestions'))->toBe([]);
});

it('validates suggest requests', function () {
    $hostname = $this->searchDomain->hostname;

    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search/suggest'))->assertUnprocessable();
    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search/suggest?q='.str_repeat('a', 101)))->assertUnprocessable();
    $this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search/suggest?q=sun&limit=99'))->assertUnprocessable();
});

it('reindexes the search index via the admin api', function () {
    $product = Product::factory()->create(['store_id' => $this->searchStore->id, 'title' => 'Unindexed Widget', 'status' => 'active', 'published_at' => now()]);

    $hostname = $this->searchDomain->hostname;
    expect($this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=unindexed'))->json('pagination.total'))->toBe(0);

    $reindex = $this->postJson("/api/admin/v1/stores/{$this->searchStore->id}/search/reindex", [],
        CatalogTestHelper::adminHeaders($this->searchUser));
    $reindex->assertAccepted();
    $reindex->assertJsonPath('message', 'Reindex job queued.');
    $reindex->assertJsonPath('status', 'queued');

    expect($this->getJson(storefrontSearchUrl($hostname, '/api/storefront/v1/search?q=unindexed'))->json('pagination.total'))->toBe(1);

    $status = $this->getJson("/api/admin/v1/stores/{$this->searchStore->id}/search/status",
        CatalogTestHelper::adminHeaders($this->searchUser));
    $status->assertOk();
    $status->assertJsonPath('data.store_id', $this->searchStore->id);
    $status->assertJsonPath('data.index_status', 'ready');
    expect($status->json('data.documents_count'))->toBeGreaterThanOrEqual(1);
    expect($status->json('data.last_reindex_at'))->not->toBeNull();
});

it('restricts search admin endpoints to owners and admins', function () {
    $staff = \App\Models\User::factory()->create();
    \App\Models\StoreUser::factory()->create(['store_id' => $this->searchStore->id, 'user_id' => $staff->id, 'role' => \App\Enums\StoreUserRole::Staff]);

    $this->postJson("/api/admin/v1/stores/{$this->searchStore->id}/search/reindex", [],
        CatalogTestHelper::adminHeaders($staff))->assertForbidden();

    $this->app['auth']->forgetGuards();
    $this->getJson("/api/admin/v1/stores/{$this->searchStore->id}/search/status",
        CatalogTestHelper::adminHeaders($staff))->assertForbidden();

    $this->app['auth']->forgetGuards();
    $this->postJson("/api/admin/v1/stores/{$this->searchStore->id}/search/reindex", [],
        CatalogTestHelper::adminHeaders($this->searchUser, ['read-settings']))->assertForbidden();
});
