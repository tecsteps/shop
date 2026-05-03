<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\CartDrawer;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
    app()->instance('current_store', Store::query()->where('handle', 'acme-fashion')->firstOrFail());
});

test('product page adds a line to the session cart and cart page starts checkout', function () {
    $product = Product::query()->where('handle', 'linen-shirt')->firstOrFail();

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors()
        ->assertDispatched('cart-updated')
        ->assertDispatched('open-cart');

    $cart = Cart::withoutGlobalScopes()->findOrFail(session('cart_id'));

    expect($cart->lines()->first())->quantity->toBe(2);

    Livewire::test(CartShow::class)
        ->set('email', 'buyer@example.com')
        ->call('startCheckout')
        ->assertHasNoErrors();

    expect($cart->refresh()->checkouts)->toHaveCount(1);
});

test('sold out deny policy variants cannot be added to the cart', function (): void {
    $product = Product::query()->where('handle', 'sold-out-canvas-sneaker')->firstOrFail();

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->assertSee('Out of stock')
        ->call('addToCart')
        ->assertHasErrors('quantity');

    expect(session('cart_id'))->toBeNull();
});

test('backorder variants can be added despite zero available stock', function (): void {
    $product = Product::query()->where('handle', 'backorder-utility-vest')->firstOrFail();

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->assertSee('Available on backorder')
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors()
        ->assertDispatched('cart-updated')
        ->assertDispatched('open-cart');

    $cart = Cart::withoutGlobalScopes()->findOrFail(session('cart_id'));

    expect($cart->lines()->first())->quantity->toBe(2);
});

test('cart drawer opens from cart events and updates line quantities', function (): void {
    $product = Product::query()->where('handle', 'linen-shirt')->firstOrFail();

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors();

    $cart = Cart::withoutGlobalScopes()->with('lines')->findOrFail(session('cart_id'));
    $line = $cart->lines->first();

    Livewire::test(CartDrawer::class)
        ->dispatch('open-cart')
        ->assertSet('open', true)
        ->assertSee('Linen Shirt')
        ->call('incrementLine', $line->id)
        ->assertHasNoErrors()
        ->call('decrementLine', $line->id)
        ->assertHasNoErrors()
        ->call('removeLine', $line->id)
        ->assertHasNoErrors();

    expect($cart->lines()->whereKey($line->id)->exists())->toBeFalse();
});

test('cart drawer applies and removes discount codes inline', function (): void {
    $product = Product::query()->where('handle', 'linen-shirt')->firstOrFail();

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->set('quantity', 1)
        ->call('addToCart')
        ->assertHasNoErrors();

    $cart = Cart::withoutGlobalScopes()->findOrFail(session('cart_id'));

    Livewire::test(CartDrawer::class)
        ->dispatch('open-cart')
        ->set('discountCode', 'welcome10')
        ->call('applyDiscount')
        ->assertHasNoErrors()
        ->assertSee('WELCOME10')
        ->assertSee('Discount')
        ->call('removeDiscount')
        ->assertHasNoErrors()
        ->assertDontSee('WELCOME10');

    $cart = $cart->refresh()->load('lines');

    expect($cart->discount_code)->toBeNull()
        ->and($cart->discountAmount())->toBe(0)
        ->and($cart->totalAmount())->toBe($cart->subtotalAmount());
});

test('cart page applies discounts before checkout starts', function (): void {
    $product = Product::query()->where('handle', 'linen-shirt')->firstOrFail();

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->set('quantity', 1)
        ->call('addToCart')
        ->assertHasNoErrors();

    $cart = Cart::withoutGlobalScopes()->findOrFail(session('cart_id'));

    Livewire::test(CartShow::class)
        ->set('discountCode', 'welcome10')
        ->call('applyDiscount')
        ->assertHasNoErrors()
        ->set('email', 'buyer@example.com')
        ->call('startCheckout')
        ->assertHasNoErrors();

    $checkout = $cart->refresh()->checkouts()->firstOrFail();

    expect($checkout->discount_code)->toBe('WELCOME10')
        ->and($checkout->totals_json['discount'])->toBe(500);
});
