<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminTokenFor($test, array $abilities): string
{
    return app(ApiTokenService::class)->create($test->store, $test->user, 'Product API test', $abilities)['plain_text_token'];
}

test('admin product api lists products with read token ability', function (): void {
    $url = route('api.admin.products.index', $this->store);

    $this->getJson($url)->assertUnauthorized();

    $this->withToken(adminTokenFor($this, ['write-products']))
        ->getJson($url)
        ->assertForbidden();

    $this->withToken(adminTokenFor($this, ['read-products']))
        ->getJson($url.'?query=linen&per_page=5')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Linen Shirt')
        ->assertJsonPath('data.0.store_id', $this->store->id)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'handle',
                    'status',
                    'variants_count',
                    'total_inventory',
                    'featured_image',
                    'variants',
                ],
            ],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
});

test('admin product api creates a product with variant inventory and collection assignment', function (): void {
    $collection = $this->store->collections()->firstOrFail();
    $payload = [
        'title' => 'Canvas API Tote',
        'status' => 'active',
        'vendor' => 'Acme',
        'product_type' => 'Accessories',
        'tags' => ['bags', 'canvas'],
        'collections' => [$collection->id],
        'variants' => [
            [
                'sku' => 'API-TOTE-001',
                'price_amount' => 3200,
                'currency' => 'EUR',
                'is_default' => true,
                'inventory' => [
                    'quantity_on_hand' => 12,
                    'policy' => InventoryPolicy::Continue->value,
                ],
            ],
        ],
    ];

    $response = $this->withToken(adminTokenFor($this, ['write-products']))
        ->postJson(route('api.admin.products.store', $this->store), $payload)
        ->assertCreated()
        ->assertJsonPath('data.title', 'Canvas API Tote')
        ->assertJsonPath('data.handle', 'canvas-api-tote')
        ->assertJsonPath('data.variants.0.sku', 'API-TOTE-001')
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 12)
        ->assertJsonPath('data.variants.0.inventory.policy', 'continue')
        ->assertJsonPath('data.collections.0.id', $collection->id);

    $product = Product::query()->whereKey($response->json('data.id'))->firstOrFail();

    expect($product->store_id)->toBe($this->store->id)
        ->and($product->variants()->firstOrFail()->inventoryItem->quantity_on_hand)->toBe(12)
        ->and($collection->products()->whereKey($product->id)->exists())->toBeTrue();
});

test('admin product api updates products and archives them on delete', function (): void {
    $product = Product::query()
        ->where('store_id', $this->store->id)
        ->where('handle', 'linen-shirt')
        ->firstOrFail();
    $variant = $product->variants()->firstOrFail();

    $this->withToken(adminTokenFor($this, ['write-products']))
        ->putJson(route('api.admin.products.update', [$this->store, $product]), [
            'title' => 'Linen Shirt API Updated',
            'tags' => ['linen', 'updated'],
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => 'LINEN-API-UPDATED',
                    'price_amount' => 6999,
                    'inventory' => [
                        'quantity_on_hand' => 15,
                        'policy' => InventoryPolicy::Deny->value,
                    ],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Linen Shirt API Updated')
        ->assertJsonPath('data.variants.0.sku', 'LINEN-API-UPDATED')
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 15);

    expect($product->refresh()->title)->toBe('Linen Shirt API Updated')
        ->and($variant->refresh()->price_amount)->toBe(6999)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(15);

    $this->withToken(adminTokenFor($this, ['write-products']))
        ->deleteJson(route('api.admin.products.destroy', [$this->store, $product]))
        ->assertOk()
        ->assertJsonPath('data.status', ProductStatus::Archived->value);

    expect($product->refresh()->status)->toBe(ProductStatus::Archived);
});

test('admin product api enforces store scoped token access and product lookup', function (): void {
    $otherStoreProduct = Product::query()
        ->where('store_id', $this->otherStore->id)
        ->firstOrFail();

    $this->withToken(adminTokenFor($this, ['read-products']))
        ->getJson(route('api.admin.products.index', $this->otherStore))
        ->assertForbidden();

    $this->withToken(adminTokenFor($this, ['read-products']))
        ->getJson(route('api.admin.products.show', [$this->store, $otherStoreProduct]))
        ->assertNotFound();
});
