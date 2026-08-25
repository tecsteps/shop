<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\InventoryItem;
use App\Services\CartService;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use App\Services\InventoryService;

it('builds the core stack end to end', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = app(ProductService::class)->create($store, [
        'title' => 'Test Product',
        'status' => 'draft',
        'price_amount' => 2500,
        'quantity_on_hand' => 10,
    ]);

    expect($product->handle)->toBe('test-product');
    expect($product->variants()->count())->toBe(1);
    expect($product->variants()->first()->is_default)->toBeTrue();
    expect($product->variants()->first()->inventoryItem->quantity_on_hand)->toBe(10);

    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, $product->variants()->first()->id, 2);

    expect($line->quantity)->toBe(2);
    expect($line->line_subtotal_amount)->toBe(5000);
    expect($cart->fresh()->cart_version)->toBe(2);
});

it('syncs products to the FTS index', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create(['title' => 'Blue Cotton T-Shirt']);

    $rows = \Illuminate\Support\Facades\DB::table('products_fts')->where('product_id', $product->id)->count();
    expect($rows)->toBeGreaterThan(0);
});
