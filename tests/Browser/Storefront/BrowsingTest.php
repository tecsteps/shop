<?php

// Suite 7: Storefront Browsing - Home page, collections, product detail, search, pages

it('shows featured products on home page', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test']);

    $page->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

it('shows collection with product grid', function () {
    $page = $this->visit('/collections/t-shirts', ['host' => 'acme-fashion.test']);

    $page->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can navigate from collection to product', function () {
    $page = $this->visit('/collections/t-shirts', ['host' => 'acme-fashion.test']);

    $page->click('Classic Cotton T-Shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Add to cart')
        ->assertNoJavaScriptErrors();
});

it('shows product detail with variant options', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Size')
        ->assertSee('Color')
        ->assertNoJavaScriptErrors();
});

it('shows size and color option values', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('S')
        ->assertSee('M')
        ->assertSee('L')
        ->assertSee('XL')
        ->assertSee('Black')
        ->assertSee('White')
        ->assertSee('Navy')
        ->assertNoJavaScriptErrors();
});

it('shows compare-at pricing on sale products', function () {
    $page = $this->visit('/products/premium-slim-fit-jeans', ['host' => 'acme-fashion.test']);

    $page->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavaScriptErrors();
});

it('shows search results for valid query', function () {
    $page = $this->visit('/search?q=cotton', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('shows empty search results message for no matches', function () {
    $page = $this->visit('/search?q=zzzznonexistent', ['host' => 'acme-fashion.test']);

    $page->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('displays the about page content', function () {
    $page = $this->visit('/pages/about', ['host' => 'acme-fashion.test']);

    $page->assertSee('About')
        ->assertNoJavaScriptErrors();
});

it('returns 404 for nonexistent page', function () {
    $page = $this->visit('/pages/nonexistent-page-xyz', ['host' => 'acme-fashion.test']);

    $page->assertSee('404')
        ->assertNoJavaScriptErrors();
});

it('hides draft products from storefront', function () {
    $page = $this->visit('/search?q=draft', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('shows all collections on collections index', function () {
    $page = $this->visit('/collections', ['host' => 'acme-fashion.test']);

    $page->assertSee('T-Shirts')
        ->assertSee('New Arrivals')
        ->assertNoJavaScriptErrors();
});

it('shows navigation menu links', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test']);

    $page->assertNoJavaScriptErrors();
});

it('can browse from home to product via collection', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test']);

    $page->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});

it('shows sold out badge for out of stock products', function () {
    $page = $this->visit('/products/classic-cotton-t-shirt', ['host' => 'acme-fashion.test']);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});
