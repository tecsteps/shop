<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\CartCount;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Livewire\Storefront\Search\Index as SearchIndex;
use App\Models\Cart;
use App\Services\CartService;
use Livewire\Livewire;

it('renders storefront analytics and dispatches product add events for the ingestion client', function () {
    $store = shopStore();
    $product = shopProduct($store);
    app()->instance('current_store', $store);

    $this->get('http://shop.test/products/'.$product['product']->handle)
        ->assertOk()
        ->assertSee('data-storefront-analytics', false)
        ->assertSee('/api/storefront/v1/analytics/events', false);

    Livewire::test(ProductShow::class, ['handle' => $product['product']->handle])
        ->call('addToCart')
        ->assertDispatched('storefront-analytics', type: 'add_to_cart');
});

it('dispatches search and cart removal events after customer actions succeed', function () {
    $store = shopStore();
    $product = shopProduct($store);
    app()->instance('current_store', $store);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    session()->put('cart_id', $cart->id);
    app(CartService::class)->add($cart, $product['variant'], 2);
    $line = $cart->lines()->firstOrFail();

    Livewire::test(SearchIndex::class)
        ->set('query', 'cotton shirt')
        ->assertDispatched('storefront-analytics', type: 'search');

    Livewire::test(CartShow::class)
        ->call('removeItem', $line->id)
        ->assertDispatched('storefront-analytics', type: 'remove_from_cart');
});

it('refreshes the storefront header cart count after cart updates', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $product = shopProduct($store);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    session()->put('cart_id', $cart->id);

    $counter = Livewire::test(CartCount::class)
        ->assertSee('Open shopping cart, 0 items');

    app(CartService::class)->add($cart, $product['variant'], 2);

    $counter->dispatch('cart-updated')
        ->assertSee('Open shopping cart, 2 items');
});
