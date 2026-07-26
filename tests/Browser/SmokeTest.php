<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

test('loads the storefront home page', function () {
    visit('/')
        ->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});

test('loads a collection page', function () {
    visit('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertNoJavaScriptErrors();
});

test('loads a product page', function () {
    visit('/products/classic-cotton-t-shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

test('loads the cart page', function () {
    visit('/cart')
        ->assertSee('Your Cart')
        ->assertNoJavaScriptErrors();
});

test('loads the customer login page', function () {
    visit('/account/login')
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});

test('loads the admin login page', function () {
    visit('/admin/login')
        ->assertSee('Sign in')
        ->assertNoJavaScriptErrors();
});

test('loads the about page', function () {
    visit('/pages/about')
        ->assertSee('About')
        ->assertNoJavaScriptErrors();
});

test('loads the search page', function () {
    visit('/search?q=shirt')
        ->assertSee('shirt')
        ->assertNoJavaScriptErrors();
});

test('loads all collections listing', function () {
    visit('/collections')
        ->assertSee('Collections')
        ->assertNoJavaScriptErrors();
});

test('has no errors on critical pages', function () {
    visit([
        '/',
        '/collections/new-arrivals',
        '/products/classic-cotton-t-shirt',
        '/cart',
        '/account/login',
        '/admin/login',
        '/pages/about',
        '/search?q=shirt',
    ])->assertNoJavaScriptErrors();
});
