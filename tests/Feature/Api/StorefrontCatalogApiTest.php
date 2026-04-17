<?php

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-14 12:00:00'));
    Cache::flush();
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function storefrontCatalogApiUrl(string $hostname, string $path): string
{
    return 'http://'.$hostname.$path;
}

function storefrontCatalogApiCreateStore(string $hostname): Store
{
    $organization = Organization::query()->create([
        'name' => 'Catalog API Org',
        'billing_email' => 'billing+catalog-api@example.test',
    ]);

    $store = Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Catalog API Store',
        'handle' => 'catalog-api-store',
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);

    StoreDomain::query()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
        'type' => 'storefront',
        'is_primary' => true,
        'tls_mode' => 'managed',
        'created_at' => now(),
    ]);

    return $store;
}

function storefrontCatalogApiCreateProduct(
    Store $store,
    string $title,
    string $handle,
    string $status = 'active',
): Product {
    $product = Product::query()->create([
        'store_id' => $store->id,
        'title' => $title,
        'handle' => $handle,
        'status' => $status,
        'description_html' => null,
        'vendor' => 'Fixture',
        'product_type' => 'Fixture',
        'tags' => ['fixture'],
        'published_at' => $status === 'active' ? now() : null,
    ]);

    ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SKU-CAT-'.$handle,
        'barcode' => null,
        'price_amount' => 2499,
        'compare_at_amount' => null,
        'currency' => 'EUR',
        'weight_g' => 250,
        'requires_shipping' => true,
        'is_default' => true,
        'position' => 1,
        'status' => 'active',
    ]);

    return $product;
}

test('search returns matching active products and logs search query', function (): void {
    $hostname = 'catalog-search.test';
    $store = storefrontCatalogApiCreateStore($hostname);

    $activeProduct = storefrontCatalogApiCreateProduct(
        store: $store,
        title: 'Blue Cotton T-Shirt',
        handle: 'blue-cotton-t-shirt',
        status: 'active',
    );

    storefrontCatalogApiCreateProduct(
        store: $store,
        title: 'Draft Cotton Prototype',
        handle: 'draft-cotton-prototype',
        status: 'draft',
    );

    $response = $this->getJson(
        storefrontCatalogApiUrl($hostname, '/api/storefront/v1/search?query=cotton'),
    );

    $response->assertOk()
        ->assertJsonFragment(['id' => $activeProduct->id, 'handle' => $activeProduct->handle])
        ->assertJsonMissing(['handle' => 'draft-cotton-prototype']);

    $this->assertDatabaseHas('search_queries', [
        'store_id' => $store->id,
        'query' => 'cotton',
    ]);
});

test('search returns empty data for unknown terms', function (): void {
    $hostname = 'catalog-search-empty.test';
    $store = storefrontCatalogApiCreateStore($hostname);
    storefrontCatalogApiCreateProduct($store, 'Blue Cotton T-Shirt', 'blue-cotton', 'active');

    $response = $this->getJson(
        storefrontCatalogApiUrl($hostname, '/api/storefront/v1/search?query=zznonexistentzz'),
    );

    $response->assertOk()
        ->assertJsonPath('data', []);

    $this->assertDatabaseHas('search_queries', [
        'store_id' => $store->id,
        'query' => 'zznonexistentzz',
        'results_count' => 0,
    ]);
});

test('analytics ingestion accepts valid event batches and stores events', function (): void {
    $hostname = 'catalog-analytics-valid.test';
    $store = storefrontCatalogApiCreateStore($hostname);

    $response = $this->postJson(
        storefrontCatalogApiUrl($hostname, '/api/storefront/v1/analytics/events'),
        [
            'events' => [
                [
                    'type' => 'page_view',
                    'session_id' => 'sess_abc123',
                    'client_event_id' => 'evt_001',
                    'properties' => [
                        'url' => '/products/blue-cotton-t-shirt',
                    ],
                    'occurred_at' => now()->toIso8601String(),
                ],
                [
                    'type' => 'add_to_cart',
                    'session_id' => 'sess_abc123',
                    'client_event_id' => 'evt_002',
                    'properties' => [
                        'product_id' => 10,
                        'variant_id' => 11,
                        'quantity' => 1,
                    ],
                    'occurred_at' => now()->toIso8601String(),
                ],
            ],
        ],
    );

    $response->assertStatus(202)
        ->assertJsonPath('accepted', 2)
        ->assertJsonPath('rejected', 0);

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $store->id,
        'type' => 'page_view',
        'client_event_id' => 'evt_001',
    ]);
});

test('analytics ingestion rejects invalid event payloads', function (): void {
    $hostname = 'catalog-analytics-invalid.test';
    storefrontCatalogApiCreateStore($hostname);

    $this->postJson(
        storefrontCatalogApiUrl($hostname, '/api/storefront/v1/analytics/events'),
        [
            'events' => [
                [
                    'type' => 'unknown_type',
                    'session_id' => 'sess_abc123',
                    'client_event_id' => 'evt_invalid',
                    'properties' => [],
                    'occurred_at' => now()->toIso8601String(),
                ],
            ],
        ],
    )->assertUnprocessable();
});
