<?php

use App\Enums\MediaStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('catalog models expose relationships and casts', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->for($store)->create(['tags' => ['summer', 'sale']]);
    $option = ProductOption::factory()->for($product)->create(['name' => 'Size']);
    $small = $option->values()->create(['value' => 'S', 'position' => 0]);
    $variant = ProductVariant::factory()->for($product)->default()->create(['price_amount' => 2499]);
    $variant->optionValues()->attach($small);
    $collection = Collection::factory()->for($store)->create();
    $media = ProductMedia::factory()->for($product)->processing()->create();

    DB::table('collection_products')->insert([
        'collection_id' => $collection->id,
        'product_id' => $product->id,
        'position' => 0,
    ]);

    expect($product->fresh()->tags)->toBe(['summer', 'sale'])
        ->and($product->variants)->toHaveCount(1)
        ->and($variant->fresh()->inventoryItem)->not->toBeNull()
        ->and($variant->fresh()->optionValues)->toHaveCount(1)
        ->and($collection->fresh()->products)->toHaveCount(1)
        ->and($media->fresh()->status)->toBe(MediaStatus::Processing);
});

test('store scoped catalog queries only return the current store records', function () {
    $currentStore = Store::factory()->create();
    $otherStore = Store::factory()->create();

    Product::factory()->for($currentStore)->create(['title' => 'Visible']);
    Product::factory()->for($otherStore)->create(['title' => 'Hidden']);

    app()->instance('current_store', $currentStore);

    expect(Product::query()->pluck('title')->all())->toBe(['Visible']);
});
