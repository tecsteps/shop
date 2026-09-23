<?php

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('creates a draft product with a default variant and unique handle', function () {
    $store = shopStore();
    $products = app(ProductService::class);
    $first = $products->create($store, [
        'title' => 'Summer Shirt',
        'variants' => [['price_amount' => 2500, 'quantity_on_hand' => 4]],
    ]);
    $second = $products->create($store, [
        'title' => 'Summer Shirt',
        'variants' => [['price_amount' => 2900, 'quantity_on_hand' => 3]],
    ]);

    expect($first->status)->toBe('draft')
        ->and($first->handle)->toBe('summer-shirt')
        ->and($second->handle)->toBe('summer-shirt-1')
        ->and($first->variants)->toHaveCount(1)
        ->and($first->variants->first()->inventoryItem->quantity_on_hand)->toBe(4);
});

it('publishes a priced product and emits its status change', function () {
    $store = shopStore();
    $product = app(ProductService::class)->create($store, [
        'title' => 'Cotton Shirt',
        'variants' => [['price_amount' => 2500]],
    ]);
    Event::fake([ProductStatusChanged::class]);

    app(ProductService::class)->transitionStatus($product, ProductStatus::Active);

    expect($product->refresh()->status)->toBe('active')
        ->and($product->published_at)->not->toBeNull();
    Event::assertDispatched(ProductStatusChanged::class);
});

it('rejects publishing a product without a priced variant', function () {
    $store = shopStore();
    $product = app(ProductService::class)->create($store, [
        'title' => 'Free Draft',
        'variants' => [['price_amount' => 0]],
    ]);

    expect(fn () => app(ProductService::class)->transitionStatus($product, ProductStatus::Active))
        ->toThrow(InvalidProductTransitionException::class);
    expect($product->refresh()->status)->toBe('draft');
});

it('rejects a duplicate SKU inside one store but permits it in another', function () {
    $store = shopStore();
    $products = app(ProductService::class);
    $products->create($store, ['title' => 'First', 'variants' => [['sku' => 'SHIRT-1', 'price_amount' => 2500]]]);

    expect(fn () => $products->create($store, ['title' => 'Duplicate', 'variants' => [['sku' => 'SHIRT-1', 'price_amount' => 2600]]]))
        ->toThrow(ValidationException::class);

    $otherStore = shopStore('other-shop.test');
    expect(fn () => $products->create($otherStore, ['title' => 'Other', 'variants' => [['sku' => 'SHIRT-1', 'price_amount' => 2600]]]))
        ->not->toThrow(ValidationException::class);
});

it('blocks product deletion after the product is published', function () {
    $store = shopStore();
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);

    expect(fn () => app(ProductService::class)->delete($product))
        ->toThrow(InvalidProductTransitionException::class);
});

it('indexes product type in the store-scoped FTS table', function () {
    $store = shopStore();
    $product = app(ProductService::class)->create($store, [
        'title' => 'Lightweight Rain Jacket',
        'product_type' => 'Outerwear',
        'description_html' => '<p>Waterproof shell</p>',
        'vendor' => 'North Wind',
        'tags' => ['rain', 'jacket'],
        'variants' => [['price_amount' => 9900]],
    ]);

    $indexedProduct = DB::table('products_fts')->where('product_id', (string) $product->id)->first();
    $matchedProductId = DB::selectOne(
        'SELECT product_id FROM products_fts WHERE products_fts MATCH ? AND store_id = ?',
        ['Outerwear', (string) $store->id],
    )?->product_id;

    expect($indexedProduct?->product_type)->toBe('Outerwear')
        ->and((int) $matchedProductId)->toBe($product->id);
});
