<?php

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;

it('creates inventory item when variant is created', function () {
    $context = createStoreContext();
    $service = app(ProductService::class);

    $product = $service->create($context['store'], ['title' => 'Inventory Test']);

    $variant = $product->variants->first();
    $inventoryItem = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->id)->first();

    expect($inventoryItem)->not->toBeNull()
        ->and($inventoryItem->quantity_on_hand)->toBe(0)
        ->and($inventoryItem->quantity_reserved)->toBe(0);
});

it('checks availability correctly', function () {
    $context = createStoreContext();
    $inventoryService = app(InventoryService::class);

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect($item->quantity_available)->toBe(7)
        ->and($inventoryService->checkAvailability($item, 7))->toBeTrue()
        ->and($inventoryService->checkAvailability($item, 8))->toBeFalse();
});

it('reserves inventory', function () {
    $context = createStoreContext();
    $inventoryService = app(InventoryService::class);

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $inventoryService->reserve($item, 3);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(3)
        ->and($item->quantity_available)->toBe(7);
});

it('throws InsufficientInventoryException when reserving more than available with deny policy', function () {
    $context = createStoreContext();
    $inventoryService = app(InventoryService::class);

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    expect(fn () => $inventoryService->reserve($item, 3))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows overselling with continue policy', function () {
    $context = createStoreContext();
    $inventoryService = app(InventoryService::class);

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $inventoryService->reserve($item, 5);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(5);
});

it('releases reserved inventory', function () {
    $context = createStoreContext();
    $inventoryService = app(InventoryService::class);

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 5,
        'policy' => InventoryPolicy::Deny,
    ]);

    $inventoryService->release($item, 3);

    $item->refresh();
    expect($item->quantity_reserved)->toBe(2);
});

it('commits inventory on order completion', function () {
    $context = createStoreContext();
    $inventoryService = app(InventoryService::class);

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 3,
        'policy' => InventoryPolicy::Deny,
    ]);

    $inventoryService->commit($item, 3);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);
});

it('restocks inventory', function () {
    $context = createStoreContext();
    $inventoryService = app(InventoryService::class);

    $product = Product::factory()->create(['store_id' => $context['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $item = InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 5,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $inventoryService->restock($item, 10);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(15);
});
