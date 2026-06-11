<?php

it('loads the storefront home page', function (): void {
    $page = visit('/');

    $page->assertSee('Acme Fashion')
        ->assertNoJavascriptErrors();
});

it('loads a collection page', function (): void {
    $page = visit('/collections/t-shirts');

    $page->assertSee('T-Shirts')
        ->assertNoJavascriptErrors();
});

it('loads a product page', function (): void {
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});

it('loads the cart page', function (): void {
    $page = visit('/cart');

    $page->assertSee('Your Cart')
        ->assertNoJavascriptErrors();
});

it('loads the customer login page', function (): void {
    $page = visit('/account/login');

    $page->assertSee('Log in')
        ->assertNoJavascriptErrors();
});

it('loads the admin login page', function (): void {
    $page = visit('/admin/login');

    $page->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

it('loads the about page', function (): void {
    $page = visit('/pages/about');

    $page->assertSee('About')
        ->assertNoJavascriptErrors();
});

it('loads the search page', function (): void {
    $page = visit('/search?q=shirt');

    $page->assertSee('shirt')
        ->assertNoJavascriptErrors();
});

it('loads all collections listing', function (): void {
    $page = visit('/collections');

    $page->assertSee('Collections')
        ->assertNoJavascriptErrors();
});

it('has no errors on critical pages', function (): void {
    $pages = visit([
        '/',
        '/collections/new-arrivals',
        '/products/classic-cotton-t-shirt',
        '/cart',
        '/account/login',
        '/admin/login',
        '/pages/about',
        '/search?q=shirt',
    ]);

    $pages->assertNoJavascriptErrors();
});
