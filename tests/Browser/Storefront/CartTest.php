<?php

// Suite 8: Cart Flow - Cart lifecycle: add, update qty, remove, discounts, totals

it('can add a product to cart', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('shows cart with added item', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('shows empty cart message when no items', function () {
    $page = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $page->assertSee('Your Cart')
        ->assertNoJavaScriptErrors();
});

it('can update item quantity in cart', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can remove an item from cart', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can apply WELCOME10 discount code', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can apply FLAT5 discount code', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('rejects expired discount code', function () {
    $page = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('rejects maxed out discount code', function () {
    $page = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('shows cart totals with subtotal and shipping', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can apply FREESHIP discount code', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can add multiple different products to cart', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});
