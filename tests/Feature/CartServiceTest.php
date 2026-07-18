<?php

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates carts and combines duplicate variants while incrementing the version', function () {
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1250]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
    ]);
    $service = app(CartService::class);
    $cart = $service->create($store);
    $service->addLine($cart, $variant->id, 1);
    $line = $service->addLine($cart->refresh(), $variant->id, 2);

    expect($cart->refresh()->currency)->toBe('EUR')
        ->and($cart->cart_version)->toBe(3)
        ->and($cart->lines()->count())->toBe(1)
        ->and($line->quantity)->toBe(3)
        ->and($line->line_total_amount)->toBe(3750);
});

it('removes a line when quantity is updated to zero', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    InventoryItem::factory()->create(['store_id' => $store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 5]);
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 1);

    $service->updateLineQuantity($cart->refresh(), $line->id, 0);

    expect($cart->lines()->count())->toBe(0);
});
