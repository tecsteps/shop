<?php

use App\Enums\CollectionStatus;
use App\Enums\InventoryPolicy;
use App\Enums\MediaStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('persists the complete catalog relationship graph from factories', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->for($store)->create();
    $option = ProductOption::factory()->for($product)->create();
    $value = ProductOptionValue::factory()->for($option, 'option')->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2599]);
    $inventoryItem = InventoryItem::factory()->for($store)->for($variant, 'variant')->create();
    $media = ProductMedia::factory()->for($product)->create();
    $collection = Collection::factory()->for($store)->create();

    $variant->optionValues()->attach($value);
    $collection->products()->attach($product, ['position' => 1]);
    app()->instance('current_store', $store);

    expect($product->fresh()->options)->toHaveCount(1)
        ->and($product->fresh()->variants)->toHaveCount(1)
        ->and($product->fresh()->media->first()->is($media))->toBeTrue()
        ->and($product->fresh()->collections->first()->is($collection))->toBeTrue()
        ->and($option->values->first()->is($value))->toBeTrue()
        ->and($variant->optionValues->first()->is($value))->toBeTrue()
        ->and($variant->inventoryItem->is($inventoryItem))->toBeTrue()
        ->and($variant->price_amount)->toBe(2599)
        ->and($inventoryItem->availableQuantity())->toBe(100);
});

it('keeps inventory factory store ownership consistent with its variant product', function () {
    $inventoryItem = InventoryItem::factory()->create();

    expect($inventoryItem->store_id)->toBe($inventoryItem->variant->product->store_id);
});

it('mirrors database defaults and casts them to backed enums', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::query()->create([
        'title' => 'Default Product',
        'handle' => 'default-product',
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
    ]);
    $inventoryItem = InventoryItem::query()->create([
        'variant_id' => $variant->id,
    ]);
    $collection = Collection::query()->create([
        'title' => 'Default Collection',
        'handle' => 'default-collection',
    ]);
    $media = ProductMedia::query()->create([
        'product_id' => $product->id,
        'storage_key' => 'products/default.jpg',
    ]);

    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($variant->status)->toBe(VariantStatus::Active)
        ->and($variant->price_amount)->toBe(0)
        ->and($inventoryItem->policy)->toBe(InventoryPolicy::Deny)
        ->and($collection->status)->toBe(CollectionStatus::Active)
        ->and($media->status)->toBe(MediaStatus::Processing);
});

it('enforces catalog enum check constraints in sqlite', function () {
    $store = Store::factory()->create();

    expect(fn () => DB::table((new Product)->getTable())->insert([
        'store_id' => $store->id,
        'title' => 'Invalid Product',
        'handle' => 'invalid-product',
        'status' => 'invalid',
        'tags' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]))
        ->toThrow(QueryException::class);
});
