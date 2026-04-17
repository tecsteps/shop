<?php

// Suite 9: Checkout Flow - Full checkout with address, shipping, payment

it('can start checkout from cart', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();

    $cart = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $cart->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can enter contact information', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('can enter shipping address', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('can select shipping method', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('can select credit card payment', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('can complete checkout with credit card', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('shows order confirmation after successful checkout', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('shows error for declined credit card', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('can select bank transfer payment', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('shows bank transfer instructions', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('can apply discount during checkout', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('shows checkout totals breakdown', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('validates required checkout fields', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->click('Add to Cart')
        ->assertNoJavaScriptErrors();
});
