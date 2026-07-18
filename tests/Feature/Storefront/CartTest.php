<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('adds a product to the cart from the product page and shows it on the cart page', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->create(['store_id' => $store->id, 'handle' => 'canvas-tote']);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'is_default' => true,
    ]);

    Livewire::test(ProductShow::class, ['handle' => 'canvas-tote'])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors()
        ->assertSet('addedToCart', true);

    expect(session('cart_id'))->not->toBeNull();

    Livewire::test(CartShow::class)
        ->assertSee($product->title)
        ->assertSee('50.00 EUR');

    expect($variant->inventoryItem->fresh()->quantity_on_hand)->toBe(10);
});

it('updates line quantity and removes a line from the cart', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->create(['store_id' => $store->id]);
    ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'is_default' => true,
    ]);

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->call('addToCart');

    $cartId = session('cart_id');
    $line = \App\Models\Cart::find($cartId)->lines->first();

    $cart = Livewire::test(CartShow::class)
        ->set("quantities.{$line->id}", 3)
        ->assertHasNoErrors();

    expect($line->fresh()->quantity)->toBe(3);

    $cart->call('removeLine', $line->id);

    expect(\App\Models\Cart::find($cartId)->lines()->count())->toBe(0);
});
