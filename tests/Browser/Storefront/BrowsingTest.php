<?php

it('shows featured products on home page', function (): void {
    $page = visit('/');

    $page->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});

it('shows collection with product grid', function (): void {
    $page = visit('/collections/t-shirts');

    $page->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavascriptErrors();
});

it('can navigate from collection to product', function (): void {
    $page = visit('/collections/t-shirts');

    $page->click('Classic Cotton T-Shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Add to cart')
        ->assertNoJavascriptErrors();
});

it('shows product detail with variant options', function (): void {
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Size')
        ->assertSee('Color')
        ->assertNoJavascriptErrors();
});

it('shows size and color option values', function (): void {
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertSee('S')
        ->assertSee('M')
        ->assertSee('L')
        ->assertSee('XL')
        ->assertVisible('label[title="Black"]')
        ->assertVisible('label[title="White"]')
        ->assertVisible('label[title="Navy"]')
        ->assertNoJavascriptErrors();
});

it('updates price when variant changes on product with compare-at pricing', function (): void {
    $page = visit('/products/premium-slim-fit-jeans');

    $page->assertSee('Premium Slim Fit Jeans')
        ->click('32')
        ->click('label[title="Blue"]')
        ->assertSee('79.99')
        ->assertVisible('s:has-text("99.99")')
        ->assertNoJavascriptErrors();
});

it('shows search results for valid query', function (): void {
    $page = visit('/search?q=cotton');

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavascriptErrors();
});

it('shows no results message for invalid query', function (): void {
    $page = visit('/search?q=zznonexistentproductzz');

    $page->assertSee('No results')
        ->assertNoJavascriptErrors();
});

it('does not show draft products on storefront collections', function (): void {
    $page = visit('/collections');

    $page->assertSee('Collections')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();
});

it('does not show draft products in search results', function (): void {
    $page = visit('/search?q=draft');

    $page->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();
});

it('shows out of stock messaging for deny-policy product', function (): void {
    $page = visit('/products/limited-edition-sneakers');

    $page->assertSee('Sold out')
        ->assertDontSee('Add to cart')
        ->assertNoJavascriptErrors();
});

it('shows backorder messaging for continue-policy product', function (): void {
    $page = visit('/products/backorder-denim-jacket');

    $page->assertSee('Available on backorder')
        ->assertButtonEnabled('Add to cart')
        ->assertNoJavascriptErrors();
});

it('shows new arrivals collection', function (): void {
    $page = visit('/collections/new-arrivals');

    $page->assertSee('New Arrivals')
        ->assertNoJavascriptErrors();
});

it('shows static about page', function (): void {
    $page = visit('/pages/about');

    $page->assertSee('About')
        ->assertNoJavascriptErrors();
});

it('navigates between pages using the main navigation', function (): void {
    $page = visit('/');

    $page->click('nav a:visible:has-text("T-Shirts")')
        ->assertPathIs('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertNoJavascriptErrors();
});
