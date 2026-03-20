<?php

// Suite 1: Smoke Tests - Hit every major page, assert HTTP 200 and no JS errors

it('loads the storefront home page', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test']);

    $page->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});

it('loads a collection page', function () {
    $page = $this->visit('/collections/t-shirts', ['host' => 'acme-fashion.test']);

    $page->assertSee('T-Shirts')
        ->assertNoJavaScriptErrors();
});

it('loads a product page', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

it('loads the cart page', function () {
    $page = $this->visit('/cart', ['host' => 'acme-fashion.test']);

    $page->assertSee('Cart')
        ->assertNoJavaScriptErrors();
});

it('loads the customer login page', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->assertSee('Customer Login')
        ->assertNoJavaScriptErrors();
});

it('loads the admin login page', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->assertSee('Admin Login')
        ->assertNoJavaScriptErrors();
});

it('loads the about page', function () {
    $page = $this->visit('/pages/about', ['host' => 'acme-fashion.test']);

    $page->assertSee('About')
        ->assertNoJavaScriptErrors();
});

it('loads the search page', function () {
    $page = $this->visit('/search?q=shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('shirt')
        ->assertNoJavaScriptErrors();
});

it('loads all collections listing', function () {
    $page = $this->visit('/collections', ['host' => 'acme-fashion.test']);

    $page->assertSee('Collections')
        ->assertNoJavaScriptErrors();
});

it('has no errors on critical pages', function () {
    $pages = $this->visit([
        '/',
        '/collections/new-arrivals',
        '/products/classic-cotton-t-shirt',
        '/cart',
        '/account/login',
        '/admin/login',
        '/pages/about',
        '/search?q=shirt',
    ], ['host' => 'acme-fashion.test']);

    $pages->assertNoJavaScriptErrors();
});
