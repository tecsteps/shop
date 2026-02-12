<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;

it('supports admin product create and update flow', function () {
    $store = Store::factory()->create();
    $user = createStoreAdmin($store);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->post('/admin/products', [
            'title' => 'Trail Backpack',
            'handle' => 'trail-backpack',
            'status' => ProductStatus::Active->value,
            'description_html' => '<p>Lightweight and durable.</p>',
            'vendor' => 'Acme',
            'product_type' => 'Bags',
            'tags' => 'trail,backpack',
            'sku' => 'TB-001',
            'price_amount' => 12999,
            'inventory_quantity' => 12,
            'inventory_policy' => InventoryPolicy::Deny->value,
        ])
        ->assertRedirect(route('admin.products.index'));

    $product = Product::query()
        ->where('store_id', $store->id)
        ->where('handle', 'trail-backpack')
        ->firstOrFail();

    $defaultVariant = $product->defaultVariant()->firstOrFail();
    $inventory = $defaultVariant->inventoryItem()->firstOrFail();

    expect($product->status)->toBe(ProductStatus::Active)
        ->and($product->tags)->toBe(['trail', 'backpack'])
        ->and($defaultVariant->sku)->toBe('TB-001')
        ->and($defaultVariant->price_amount)->toBe(12999)
        ->and($inventory->quantity_on_hand)->toBe(12)
        ->and($inventory->policy)->toBe(InventoryPolicy::Deny);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->put("/admin/products/{$product->id}", [
            'title' => 'Trail Backpack Pro',
            'handle' => 'trail-backpack-pro',
            'status' => ProductStatus::Draft->value,
            'description_html' => '<p>Updated description.</p>',
            'vendor' => 'Acme Plus',
            'product_type' => 'Travel',
            'tags' => 'trail,pro',
            'sku' => 'TB-002',
            'price_amount' => 14999,
            'inventory_quantity' => 8,
            'inventory_policy' => InventoryPolicy::ContinueSelling->value,
        ])
        ->assertRedirect(route('admin.products.edit', $product));

    $product->refresh();
    $defaultVariant->refresh();
    $inventory->refresh();

    expect($product->title)->toBe('Trail Backpack Pro')
        ->and($product->handle)->toBe('trail-backpack-pro')
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($defaultVariant->sku)->toBe('TB-002')
        ->and($defaultVariant->price_amount)->toBe(14999)
        ->and($inventory->quantity_on_hand)->toBe(8)
        ->and($inventory->policy)->toBe(InventoryPolicy::ContinueSelling);
});
