<?php

use App\Models\Product;
use App\Models\Store;

/**
 * Build the absolute storefront API URL for the store's primary domain.
 */
function searchApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Create a store, bind it as the current tenant, and return it.
 */
function apiSearchStore(): Store
{
    $store = test()->createStore();
    test()->bindStore($store);

    return $store;
}

/**
 * Create an active, published product with one priced variant.
 */
function apiSearchProduct(Store $store, array $attributes = [], array $variantAttributes = []): Product
{
    return Product::factory()
        ->active()
        ->withVariants(1, array_merge(['price_amount' => 2500], $variantAttributes))
        ->create(array_merge(['store_id' => $store->id], $attributes));
}

test('returns the documented response shape', function () {
    $store = apiSearchStore();
    apiSearchProduct($store, [
        'title' => 'Classic T-Shirt',
        'vendor' => 'Acme Apparel',
        'product_type' => 'Apparel',
        'tags' => ['organic', 'cotton'],
    ], ['price_amount' => 2500, 'compare_at_amount' => 3500]);

    $response = $this->getJson(searchApiUrl($store, '/search?q=t-shirt'));

    $response->assertOk()
        ->assertJsonPath('query', 't-shirt')
        ->assertJsonStructure([
            'query',
            'results' => [
                ['id', 'title', 'handle', 'vendor', 'product_type', 'price_amount', 'compare_at_amount', 'currency', 'image_url', 'in_stock', 'tags'],
            ],
            'facets' => [
                'vendors' => [['value', 'count']],
                'tags' => [['value', 'count']],
                'price_range' => ['min', 'max'],
            ],
            'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);

    $result = $response->json('results.0');

    expect($result['title'])->toBe('Classic T-Shirt')
        ->and($result['vendor'])->toBe('Acme Apparel')
        ->and($result['price_amount'])->toBe(2500)
        ->and($result['compare_at_amount'])->toBe(3500)
        ->and($result['in_stock'])->toBeTrue()
        ->and($result['tags'])->toBe(['organic', 'cotton']);

    expect($response->json('facets.vendors.0.value'))->toBe('Acme Apparel')
        ->and($response->json('facets.vendors.0.count'))->toBe(1)
        ->and($response->json('facets.tags'))->toHaveCount(2)
        ->and($response->json('facets.price_range.min'))->toBe(2500)
        ->and($response->json('facets.price_range.max'))->toBe(2500)
        ->and($response->json('pagination.total'))->toBe(1);
});

test('validates the query string', function () {
    $store = apiSearchStore();

    $this->getJson(searchApiUrl($store, '/search'))->assertUnprocessable();
    $this->getJson(searchApiUrl($store, '/search?q='.str_repeat('a', 201)))->assertUnprocessable();
});

test('caps the per_page parameter', function () {
    $store = apiSearchStore();

    $this->getJson(searchApiUrl($store, '/search?q=cotton&per_page=51'))->assertUnprocessable();
});

test('rejects malformed filters json and invalid sort', function () {
    $store = apiSearchStore();

    $this->getJson(searchApiUrl($store, '/search?q=cotton&filters=not-json'))->assertUnprocessable();
    $this->getJson(searchApiUrl($store, '/search?q=cotton&sort=random'))->assertUnprocessable();
});

test('applies per_page and page parameters', function () {
    $store = apiSearchStore();
    apiSearchProduct($store, ['title' => 'Cotton One']);
    apiSearchProduct($store, ['title' => 'Cotton Two']);
    apiSearchProduct($store, ['title' => 'Cotton Three']);

    $page1 = $this->getJson(searchApiUrl($store, '/search?q=cotton&per_page=2&page=1'));
    $page2 = $this->getJson(searchApiUrl($store, '/search?q=cotton&per_page=2&page=2'));

    $page1->assertOk()
        ->assertJsonPath('pagination.per_page', 2)
        ->assertJsonPath('pagination.current_page', 1)
        ->assertJsonPath('pagination.total', 3)
        ->assertJsonPath('pagination.last_page', 2);

    expect($page1->json('results'))->toHaveCount(2)
        ->and($page2->json('results'))->toHaveCount(1);
});

test('applies filters from URL-encoded JSON', function () {
    $store = apiSearchStore();
    $acme = apiSearchProduct($store, ['title' => 'Cotton Shirt', 'vendor' => 'Acme']);
    apiSearchProduct($store, ['title' => 'Cotton Shirt', 'vendor' => 'Other'], ['price_amount' => 100]);

    $filters = urlencode(json_encode(['vendor' => 'Acme', 'price_min' => 2000]));

    $response = $this->getJson(searchApiUrl($store, "/search?q=cotton&filters={$filters}"));

    $response->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('results.0.id', $acme->id);
});

test('sorts results via the sort parameter', function () {
    $store = apiSearchStore();
    $cheap = apiSearchProduct($store, ['title' => 'Cotton Cheap'], ['price_amount' => 1000]);
    $pricey = apiSearchProduct($store, ['title' => 'Cotton Pricey'], ['price_amount' => 9000]);

    $asc = $this->getJson(searchApiUrl($store, '/search?q=cotton&sort=price_asc'));
    $desc = $this->getJson(searchApiUrl($store, '/search?q=cotton&sort=price_desc'));

    expect(collect($asc->json('results'))->pluck('id')->all())->toBe([$cheap->id, $pricey->id])
        ->and(collect($desc->json('results'))->pluck('id')->all())->toBe([$pricey->id, $cheap->id]);
});

test('scopes API search to the store of the request domain', function () {
    $storeA = apiSearchStore();
    $storeB = test()->createStore();

    $own = apiSearchProduct($storeA, ['title' => 'T-Shirt']);
    apiSearchProduct($storeB, ['title' => 'T-Shirt Deluxe']);

    $response = $this->getJson(searchApiUrl($storeA, '/search?q=t-shirt'));

    $response->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('results.0.id', $own->id);
});
