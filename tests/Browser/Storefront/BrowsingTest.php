<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

test('shows featured products on home page', function () {
    visit('/')
        ->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

test('shows collection with product grid', function () {
    visit('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

test('can navigate from collection to product', function () {
    visit('/collections/t-shirts')
        ->click('Classic Cotton T-Shirt')
        ->wait(1)
        ->assertPathIs('/products/classic-cotton-t-shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Add to cart')
        ->assertNoJavaScriptErrors();
});

test('shows product detail with variant options', function () {
    visit('/products/classic-cotton-t-shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Size')
        ->assertSee('Color')
        ->assertNoJavaScriptErrors();
});

test('shows size and color option values', function () {
    visit('/products/classic-cotton-t-shirt')
        ->assertVisible('button:text-is("S")')
        ->assertVisible('button:text-is("M")')
        ->assertVisible('button:text-is("L")')
        ->assertVisible('button:text-is("XL")')
        ->assertVisible('button[aria-label="White"]')
        ->assertVisible('button[aria-label="Black"]')
        ->assertVisible('button[aria-label="Navy"]')
        ->assertNoJavaScriptErrors();
});

test('updates price when variant changes on product with compare-at pricing', function () {
    visit('/products/premium-slim-fit-jeans')
        ->assertSee('Premium Slim Fit Jeans')
        ->press('30')
        ->wait(1)
        ->assertSee('79.99')
        ->assertScript("document.querySelector('s')?.textContent.includes('99.99')")
        ->assertNoJavaScriptErrors();
});

test('shows search results for valid query', function () {
    visit('/search?q=cotton')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

test('shows no results message for invalid query', function () {
    visit('/search?q=zznonexistentproductzz')
        ->assertSee('No results')
        ->assertNoJavaScriptErrors();
});

test('does not show draft products on storefront collections', function () {
    visit('/collections')
        ->assertSee('New Arrivals')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors();
});

test('does not show draft products in search results', function () {
    visit('/search?q=draft')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors();
});

test('shows out of stock messaging for deny-policy product', function () {
    visit('/products/limited-edition-sneakers')
        ->assertSee('Sold out')
        ->assertDisabled('button[wire\:click="addToCart"]')
        ->assertNoJavaScriptErrors();
});

test('shows backorder messaging for continue-policy product', function () {
    visit('/products/backorder-denim-jacket')
        ->assertSee('Available on backorder')
        ->assertSee('Add to cart')
        ->assertEnabled('button[wire\:click="addToCart"]')
        ->assertNoJavaScriptErrors();
});

test('shows new arrivals collection', function () {
    visit('/collections/new-arrivals')
        ->assertSee('New Arrivals')
        ->assertNoJavaScriptErrors();
});

test('shows static about page', function () {
    visit('/pages/about')
        ->assertSee('About')
        ->assertNoJavaScriptErrors();
});

test('navigates between pages using the main navigation', function () {
    visit('/')
        ->click('nav[aria-label="Main navigation"] a:has-text("T-Shirts")')
        ->wait(1)
        ->assertPathIs('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertNoJavaScriptErrors();
});
