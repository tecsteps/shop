<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use App\Services\ProductService;
use Tests\Support\CatalogTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createInventoryContext(array $inventory = ['quantity_on_hand' => 10, 'quantity_reserved' => 0, 'policy' => 'deny']): array
{
    ['store' => $store, 'user' => $user] = test()->createStoreContext();

    $payload = CatalogTestHelper::productPayload();
    $payload['variants'][0]['inventory'] = ['quantity_on_hand' => $inventory['quantity_on_hand'], 'policy' => $inventory['policy']];
    $product = app(ProductService::class)->create($store, $payload);

    $item = InventoryItem::query()->where('variant_id', $product->productVariants()->first()->id)->firstOrFail();

    if (($inventory['quantity_reserved'] ?? 0) > 0) {
        $item->update(['quantity_reserved' => $inventory['quantity_reserved']]);
        $item->refresh();
    }

    return ['store' => $store, 'user' => $user, 'product' => $product->fresh(), 'item' => $item];
}

it('creates an inventory item when a variant is created', function () {
    ['store' => $store, 'item' => $item] = createInventoryContext();

    expect($item->store_id)->toBe($store->id);
    expect($item->quantity_on_hand)->toBe(10);
    expect($item->quantity_reserved)->toBe(0);
    expect($item->policy)->toBe(InventoryPolicy::Deny);
});

it('computes available quantity', function () {
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 10, 'quantity_reserved' => 3, 'policy' => 'deny']);

    expect(app(InventoryService::class)->available($item))->toBe(7);
    expect(app(InventoryService::class)->checkAvailability($item, 7))->toBeTrue();
    expect(app(InventoryService::class)->checkAvailability($item, 8))->toBeFalse();
});

it('reserves inventory', function () {
    ['item' => $item] = createInventoryContext();

    $reserved = app(InventoryService::class)->reserve($item, 3);

    expect($reserved->quantity_reserved)->toBe(3);
    expect(app(InventoryService::class)->available($reserved))->toBe(7);
});

it('throws when reserving more than available with deny policy', function () {
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 5, 'quantity_reserved' => 3, 'policy' => 'deny']);

    expect(fn () => app(InventoryService::class)->reserve($item, 3))->toThrow(InsufficientInventoryException::class);
    expect($item->fresh()->quantity_reserved)->toBe(3);
});

it('allows overselling with continue policy', function () {
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 2, 'quantity_reserved' => 0, 'policy' => 'continue']);

    $reserved = app(InventoryService::class)->reserve($item, 5);

    expect($reserved->quantity_reserved)->toBe(5);
    expect(app(InventoryService::class)->available($reserved))->toBe(-3);
});

it('releases reserved inventory', function () {
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 10, 'quantity_reserved' => 5, 'policy' => 'deny']);

    $released = app(InventoryService::class)->release($item, 3);

    expect($released->quantity_reserved)->toBe(2);
});

it('clamps released inventory at zero', function () {
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 10, 'quantity_reserved' => 2, 'policy' => 'deny']);

    $released = app(InventoryService::class)->release($item, 9);

    expect($released->quantity_reserved)->toBe(0);
});

it('commits inventory on order completion', function () {
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 10, 'quantity_reserved' => 3, 'policy' => 'deny']);

    $committed = app(InventoryService::class)->commit($item, 3);

    expect($committed->quantity_on_hand)->toBe(7);
    expect($committed->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 5, 'quantity_reserved' => 1, 'policy' => 'deny']);

    $restocked = app(InventoryService::class)->restock($item, 10);

    expect($restocked->quantity_on_hand)->toBe(15);
    expect($restocked->quantity_reserved)->toBe(1);
});

it('signals low stock', function () {
    $service = app(InventoryService::class);
    ['item' => $item] = createInventoryContext(['quantity_on_hand' => 6, 'quantity_reserved' => 2, 'policy' => 'deny']);

    expect($service->isLowStock($item))->toBeTrue();
    expect($service->isLowStock($item, 3))->toBeFalse();
});

it('adjusts inventory via the api', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createInventoryContext();
    $variant = $product->productVariants()->first();

    $response = $this->putJson(
        "/api/admin/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/inventory",
        ['quantity_on_hand' => 25, 'policy' => 'continue'],
        CatalogTestHelper::adminHeaders($user)
    );

    $response->assertOk();
    $response->assertJsonPath('data.variant_id', $variant->id);
    $response->assertJsonPath('data.quantity_on_hand', 25);
    $response->assertJsonPath('data.policy', 'continue');
    $response->assertJsonPath('data.available', 25);
    expect($response->json('data'))->toHaveKey('low_stock');
});

it('shows inventory via the api', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createInventoryContext();
    $variant = $product->productVariants()->first();

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/inventory",
        CatalogTestHelper::adminHeaders($user, ['read-products'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.quantity_on_hand', 10);
    $response->assertJsonPath('data.available', 10);
});

it('validates inventory adjustments', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createInventoryContext();
    $variant = $product->productVariants()->first();
    $headers = CatalogTestHelper::adminHeaders($user);

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/inventory",
        ['quantity_on_hand' => -1], $headers)->assertUnprocessable();

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/inventory",
        ['policy' => 'sometimes'], $headers)->assertUnprocessable();

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/inventory",
        [], $headers)->assertUnprocessable();
});

it('returns 404 for foreign inventory records', function () {
    ['store' => $storeA, 'user' => $userA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    $headersA = CatalogTestHelper::adminHeaders($userA);

    $productA = app(ProductService::class)->create($storeA, CatalogTestHelper::productPayload());
    $productB = app(ProductService::class)->create($storeB, CatalogTestHelper::productPayload());

    $variantB = $productB->productVariants()->first();

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/products/{$productA->id}/variants/{$variantB->id}/inventory", $headersA)->assertNotFound();
    $this->putJson("/api/admin/v1/stores/{$storeA->id}/products/{$productB->id}/variants/{$variantB->id}/inventory",
        ['quantity_on_hand' => 5], $headersA)->assertNotFound();
});

it('enforces abilities on inventory endpoints', function () {
    ['store' => $store, 'user' => $user, 'product' => $product] = createInventoryContext();
    $variant = $product->productVariants()->first();

    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/inventory",
        ['quantity_on_hand' => 5])->assertUnauthorized();

    $this->app['auth']->forgetGuards();
    $this->putJson("/api/admin/v1/stores/{$store->id}/products/{$product->id}/variants/{$variant->id}/inventory",
        ['quantity_on_hand' => 5], CatalogTestHelper::adminHeaders($user, ['read-products']))->assertForbidden();
});
