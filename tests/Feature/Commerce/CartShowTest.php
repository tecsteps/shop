<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders an empty cart message when no cart is set', function () {
    Livewire::test(CartShow::class)
        ->assertStatus(200)
        ->assertSee('Your cart is empty');
});

it('updates line quantity through the Livewire component', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 1000,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 50,
    ]);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, (int) $variant->getKey(), 1);
    session(['cart_id' => $cart->getKey()]);

    Livewire::test(CartShow::class)
        ->call('updateQuantity', $line->getKey(), 4)
        ->assertStatus(200);

    expect($line->fresh()->quantity)->toBe(4);
});

it('removes a line through the Livewire component', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 10,
    ]);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, (int) $variant->getKey(), 2);
    session(['cart_id' => $cart->getKey()]);

    Livewire::test(CartShow::class)
        ->call('removeLine', $line->getKey())
        ->assertStatus(200);

    expect($cart->lines()->count())->toBe(0);
});
