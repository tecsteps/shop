<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function storefrontBrowsingHost(): array
{
    return ['host' => 'shop.test'];
}

test('shows featured products on home page', function (): void {
    visit('/', storefrontBrowsingHost())
        ->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

test('shows collection with product grid', function (): void {
    visit('/collections/t-shirts', storefrontBrowsingHost())
        ->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

test('can navigate from collection to product', function (): void {
    visit('/collections/t-shirts', storefrontBrowsingHost())
        ->click('a[href$="/products/classic-cotton-t-shirt"]')
        ->wait(1)
        ->assertPathIs('/products/classic-cotton-t-shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Add to cart')
        ->assertNoJavaScriptErrors();
});

test('shows product detail with variant options', function (): void {
    visit('/products/classic-cotton-t-shirt', storefrontBrowsingHost())
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Size')
        ->assertSee('Color')
        ->assertNoJavaScriptErrors();
});

test('shows size and color option values', function (): void {
    visit('/products/classic-cotton-t-shirt', storefrontBrowsingHost())
        ->assertSee('S')
        ->assertSee('M')
        ->assertSee('L')
        ->assertSee('XL')
        ->assertSee('Black')
        ->assertSee('White')
        ->assertSee('Navy')
        ->assertNoJavaScriptErrors();
});

test('shows sale and compare at pricing for sale product', function (): void {
    visit('/products/premium-slim-fit-jeans', storefrontBrowsingHost())
        ->assertSee('Premium Slim Fit Jeans')
        ->assertSee('79.99')
        ->assertSee('99.99')
        ->assertPresent('.line-through')
        ->assertNoJavaScriptErrors();
});

test('shows search results for valid query', function (): void {
    visit('/search?q=cotton', storefrontBrowsingHost())
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

test('shows no results message for invalid query', function (): void {
    visit('/search?q=zznonexistentproductzz', storefrontBrowsingHost())
        ->assertSee('No products found')
        ->assertNoJavaScriptErrors();
});

test('does not show draft products on storefront collections', function (): void {
    visit('/collections', storefrontBrowsingHost())
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors();
});

test('does not show draft products in search results', function (): void {
    visit('/search?q=draft', storefrontBrowsingHost())
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors();
});

test('shows out of stock messaging for deny policy product', function (): void {
    visit('/products/limited-edition-sneakers', storefrontBrowsingHost())
        ->assertSee('Limited Edition Sneakers')
        ->assertSee('Out of stock')
        ->assertSee('Sold out')
        ->assertButtonDisabled('button:has-text("Sold out")')
        ->assertDontSee('Add to cart')
        ->assertNoJavaScriptErrors();
});

test('shows backorder messaging for continue policy product', function (): void {
    visit('/products/backorder-denim-jacket', storefrontBrowsingHost())
        ->assertSee('Backorder Denim Jacket')
        ->assertSee('Available on backorder')
        ->assertSee('Add to cart')
        ->assertButtonEnabled('button:has-text("Add to cart")')
        ->assertNoJavaScriptErrors();
});

test('shows new arrivals collection', function (): void {
    visit('/collections/new-arrivals', storefrontBrowsingHost())
        ->assertSee('New Arrivals')
        ->assertNoJavaScriptErrors();
});

test('shows static about page', function (): void {
    visit('/pages/about', storefrontBrowsingHost())
        ->assertSee('About')
        ->assertNoJavaScriptErrors();
});

test('navigates between pages using the main navigation', function (): void {
    visit('/', storefrontBrowsingHost())
        ->hover('nav[aria-label="Main navigation"] a[href$="/collections"]')
        ->click('nav[aria-label="Main navigation"] a[href$="/collections/t-shirts"]')
        ->wait(1)
        ->assertPathIs('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertNoJavaScriptErrors();
});
