<?php

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

test('product belongs to store', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);

    expect($product->store->id)->toBe($this->store->id);
});

test('product has many variants', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

    expect($product->variants)->toHaveCount(3);
});

test('product has many options', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductOption::factory()->create(['product_id' => $product->id, 'position' => 0]);
    ProductOption::factory()->create(['product_id' => $product->id, 'position' => 1]);

    expect($product->options)->toHaveCount(2);
});

test('product has many media', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    ProductMedia::factory()->count(2)->create(['product_id' => $product->id]);

    expect($product->media)->toHaveCount(2);
});

test('product belongs to many collections', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $collection->products()->attach($product->id, ['position' => 0]);

    expect($product->collections)->toHaveCount(1);
    expect($product->collections->first()->id)->toBe($collection->id);
});

test('product casts tags as array', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'tags' => ['summer', 'sale'],
    ]);

    $product->refresh();
    expect($product->tags)->toBeArray()->toHaveCount(2);
    expect($product->tags)->toContain('summer', 'sale');
});

test('product casts status as enum', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);

    expect($product->status)->toBe(ProductStatus::Active);
});

test('product option has many values', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $option = ProductOption::factory()->create(['product_id' => $product->id, 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'position' => 0]);
    ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'position' => 1]);

    expect($option->values)->toHaveCount(2);
});

test('product variant belongs to many option values', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $option = ProductOption::factory()->create(['product_id' => $product->id, 'position' => 0]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'position' => 0]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $variant->optionValues()->attach($value->id);

    expect($variant->optionValues)->toHaveCount(1);
    expect($variant->optionValues->first()->id)->toBe($value->id);
});

test('product variant has one inventory item', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    expect($variant->inventoryItem)->not->toBeNull();
    expect($variant->inventoryItem->variant_id)->toBe($variant->id);
});

test('product variant casts boolean fields', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => true,
        'is_default' => false,
    ]);

    expect($variant->requires_shipping)->toBeTrue();
    expect($variant->is_default)->toBeFalse();
});

test('collection belongs to many products', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $product1 = Product::factory()->create(['store_id' => $this->store->id]);
    $product2 = Product::factory()->create(['store_id' => $this->store->id]);
    $collection->products()->attach([$product1->id => ['position' => 0], $product2->id => ['position' => 1]]);

    expect($collection->products)->toHaveCount(2);
});

test('collection casts type and status as enums', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'type' => CollectionType::Automated,
        'status' => CollectionStatus::Draft,
    ]);

    expect($collection->type)->toBe(CollectionType::Automated);
    expect($collection->status)->toBe(CollectionStatus::Draft);
});

test('product media casts type and status as enums', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $media = ProductMedia::factory()->create([
        'product_id' => $product->id,
        'type' => MediaType::Image,
        'status' => MediaStatus::Ready,
    ]);

    expect($media->type)->toBe(MediaType::Image);
    expect($media->status)->toBe(MediaStatus::Ready);
});
