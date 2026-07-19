<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;

/**
 * Build the absolute storefront API URL for the store's primary domain.
 */
function suggestApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Create a store, bind it as the current tenant, and return it.
 */
function suggestStore(): Store
{
    $store = test()->createStore();
    test()->bindStore($store);

    return $store;
}

/**
 * Create an active, published product with one priced variant.
 */
function suggestProduct(Store $store, string $title): Product
{
    return Product::factory()->active()->withVariants(1, ['price_amount' => 2500])->create([
        'store_id' => $store->id,
        'title' => $title,
    ]);
}

test('returns suggestions matching prefix', function () {
    $store = suggestStore();
    suggestProduct($store, 'Summer Dress');
    suggestProduct($store, 'Summer Hat');
    suggestProduct($store, 'Winter Coat');

    $response = $this->getJson(suggestApiUrl($store, '/search/suggest?q=sum'));

    $response->assertOk()
        ->assertJsonPath('query', 'sum');

    $titles = collect($response->json('suggestions'))->where('type', 'product')->pluck('title');

    expect($titles)->toContain('Summer Dress')
        ->toContain('Summer Hat')
        ->not->toContain('Winter Coat');
});

test('suggestion payload has the documented shape', function () {
    $store = suggestStore();
    suggestProduct($store, 'Summer Dress');

    $response = $this->getJson(suggestApiUrl($store, '/search/suggest?q=sum'));

    $response->assertOk()->assertJsonStructure([
        'query',
        'suggestions' => [
            ['type', 'title', 'handle', 'image_url', 'price_amount', 'currency'],
        ],
    ]);

    $suggestion = $response->json('suggestions.0');

    expect($suggestion['type'])->toBe('product')
        ->and($suggestion['price_amount'])->toBe(2500);
});

test('limits results to configured count', function () {
    $store = suggestStore();

    Product::factory()->active()->withVariants(1)->count(20)->create([
        'store_id' => $store->id,
        'title' => 'Summer Product',
    ]);

    $response = $this->getJson(suggestApiUrl($store, '/search/suggest?q=sum&limit=5'));

    $response->assertOk();

    expect(collect($response->json('suggestions'))->where('type', 'product'))->toHaveCount(5);
});

test('returns empty suggestions when the prefix sanitizes to nothing', function () {
    $store = suggestStore();
    suggestProduct($store, 'Summer Dress');

    // A lone quote character passes length validation but is stripped by sanitization.
    $response = $this->getJson(suggestApiUrl($store, '/search/suggest?q=%22'));

    $response->assertOk()->assertJsonPath('suggestions', []);
});

test('suggests matching collections', function () {
    $store = suggestStore();
    Collection::factory()->create(['store_id' => $store->id, 'title' => 'Summer Picks', 'status' => \App\Enums\CollectionStatus::Active]);

    $response = $this->getJson(suggestApiUrl($store, '/search/suggest?q=sum'));

    $response->assertOk();

    $collections = collect($response->json('suggestions'))->where('type', 'collection');

    expect($collections)->toHaveCount(1)
        ->and($collections->first()['title'])->toBe('Summer Picks')
        ->and($collections->first()['handle'])->not->toBeEmpty();
});

test('suggests popular past queries when few products match', function () {
    $store = suggestStore();

    SearchQuery::factory()->count(3)->create(['store_id' => $store->id, 'query' => 'sunglasses']);
    SearchQuery::factory()->create(['store_id' => $store->id, 'query' => 'summer hat']);

    $response = $this->getJson(suggestApiUrl($store, '/search/suggest?q=sun'));

    $response->assertOk();

    $queries = collect($response->json('suggestions'))->where('type', 'query')->pluck('title');

    expect($queries)->toContain('sunglasses')
        ->not->toContain('summer hat');
});

test('suggestions are scoped to the current store', function () {
    $storeA = suggestStore();
    $storeB = test()->createStore();

    suggestProduct($storeA, 'Summer Dress');
    suggestProduct($storeB, 'Summer Secret');

    $response = $this->getJson(suggestApiUrl($storeA, '/search/suggest?q=sum'));

    $titles = collect($response->json('suggestions'))->where('type', 'product')->pluck('title');

    expect($titles)->toContain('Summer Dress')
        ->not->toContain('Summer Secret');
});

test('suggest validates the query length and limit', function () {
    $store = suggestStore();

    $this->getJson(suggestApiUrl($store, '/search/suggest'))->assertUnprocessable();
    $this->getJson(suggestApiUrl($store, '/search/suggest?q='.str_repeat('a', 101)))->assertUnprocessable();
    $this->getJson(suggestApiUrl($store, '/search/suggest?q=sum&limit=11'))->assertUnprocessable();
});
