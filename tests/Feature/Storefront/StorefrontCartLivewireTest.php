<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
    app()->instance('current_store', \App\Models\Store::query()->where('handle', 'acme-fashion')->firstOrFail());
});

test('product page adds a line to the session cart and cart page starts checkout', function () {
    $product = Product::query()->where('handle', 'linen-shirt')->firstOrFail();

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors();

    $cart = Cart::withoutGlobalScopes()->findOrFail(session('cart_id'));

    expect($cart->lines()->first())->quantity->toBe(2);

    Livewire::test(CartShow::class)
        ->set('email', 'buyer@example.com')
        ->call('startCheckout')
        ->assertHasNoErrors();

    expect($cart->refresh()->checkouts)->toHaveCount(1);
});
