<?php

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function inventoryService(): InventoryService
{
    expect(class_exists(InventoryService::class))
        ->toBeTrue('App\\Services\\InventoryService is expected to exist.');

    /** @var InventoryService $service */
    $service = app(InventoryService::class);

    foreach (['checkAvailability', 'reserve', 'release', 'commit', 'restock'] as $method) {
        expect(method_exists($service, $method))
            ->toBeTrue(sprintf('InventoryService must expose %s(...).', $method));
    }

    return $service;
}

function inventoryFixture(
    InventoryPolicy $policy = InventoryPolicy::Deny,
    int $onHand = 10,
    int $reserved = 0,
): InventoryItem {
    $organization = Organization::query()->create([
        'name' => 'Inventory Org',
        'billing_email' => 'billing+inventory@example.test',
    ]);

    $store = Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Inventory Store',
        'handle' => 'inventory-store',
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);

    $product = Product::query()->create([
        'store_id' => $store->id,
        'title' => 'Inventory Product',
        'handle' => 'inventory-product',
        'status' => 'active',
        'description_html' => null,
        'vendor' => null,
        'product_type' => null,
        'tags' => [],
        'published_at' => now(),
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SKU-INV-001',
        'barcode' => null,
        'price_amount' => 2500,
        'compare_at_amount' => null,
        'currency' => 'EUR',
        'weight_g' => 250,
        'requires_shipping' => true,
        'is_default' => true,
        'position' => 1,
        'status' => 'active',
    ]);

    return InventoryItem::query()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $onHand,
        'quantity_reserved' => $reserved,
        'policy' => $policy,
    ]);
}

test('checks availability for deny policy using available quantity', function (): void {
    $service = inventoryService();
    $item = inventoryFixture(InventoryPolicy::Deny, onHand: 7, reserved: 2); // available = 5

    expect($service->checkAvailability($item, 5))->toBeTrue()
        ->and($service->checkAvailability($item, 6))->toBeFalse();
});

test('allows availability check for continue policy even when stock is insufficient', function (): void {
    $service = inventoryService();
    $item = inventoryFixture(InventoryPolicy::Continue, onHand: 0, reserved: 0);

    expect($service->checkAvailability($item, 99))->toBeTrue();
});

test('reserve increments quantity reserved', function (): void {
    $service = inventoryService();
    $item = inventoryFixture(InventoryPolicy::Deny, onHand: 10, reserved: 1);

    $service->reserve($item, 3);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(4);
});

test('reserve throws when deny policy does not have enough available inventory', function (): void {
    $service = inventoryService();
    $item = inventoryFixture(InventoryPolicy::Deny, onHand: 2, reserved: 1); // available = 1

    expect(fn () => $service->reserve($item, 2))
        ->toThrow(\App\Exceptions\InsufficientInventoryException::class);
});

test('release decrements reserved quantity', function (): void {
    $service = inventoryService();
    $item = inventoryFixture(InventoryPolicy::Deny, onHand: 12, reserved: 5);

    $service->release($item, 2);
    $item->refresh();

    expect($item->quantity_reserved)->toBe(3);
});

test('commit decrements on hand and reserved quantities', function (): void {
    $service = inventoryService();
    $item = inventoryFixture(InventoryPolicy::Deny, onHand: 20, reserved: 6);

    $service->commit($item, 4);
    $item->refresh();

    expect($item->quantity_on_hand)->toBe(16)
        ->and($item->quantity_reserved)->toBe(2);
});

test('restock increments on hand quantity', function (): void {
    $service = inventoryService();
    $item = inventoryFixture(InventoryPolicy::Deny, onHand: 3, reserved: 0);

    $service->restock($item, 7);
    $item->refresh();

    expect($item->quantity_on_hand)->toBe(10);
});
