<?php

use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
});

test('storefront home renders seeded theme collections and products', function () {
    $this->get('http://shop.test/')
        ->assertOk()
        ->assertSee('Acme Fashion')
        ->assertSee('Summer Essentials')
        ->assertSee('Linen Shirt');
});

test('collection product page static page cart and search render', function () {
    $this->get('http://shop.test/collections/summer-essentials')
        ->assertOk()
        ->assertSee('Linen Shirt');

    $this->get('http://shop.test/products/linen-shirt')
        ->assertOk()
        ->assertSee('Add to cart')
        ->assertSee('49.99 EUR');

    $this->get('http://shop.test/pages/about')
        ->assertOk()
        ->assertSee('About Acme');

    $this->get('http://shop.test/cart')
        ->assertOk()
        ->assertSee('Your cart is empty.');

    $this->get('http://shop.test/search?q=linen')
        ->assertOk()
        ->assertSee('Linen Shirt');
});

test('storefront not found page includes search and home actions', function (): void {
    $this->get('http://shop.test/products/missing-product')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Search products')
        ->assertSee('Return home');
});
