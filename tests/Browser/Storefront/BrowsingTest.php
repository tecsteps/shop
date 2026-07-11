<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

it('shows the seeded merchandising on the home page', function (): void {
    visit('/')
        ->assertTitleContains('Acme Fashion')
        ->assertSee('Find your next favorite.')
        ->assertSee('Featured collections')
        ->assertSee('Featured products')
        ->assertNoJavaScriptErrors();
});

it('browses a collection and filters its products', function (): void {
    visit('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Graphic Print Tee')
        ->fill('input[placeholder="Search this collection"]', 'Graphic')
        ->waitForText('Graphic Print Tee')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('renders product pricing, variants, inventory, and accessible media', function (): void {
    visit('/products/classic-cotton-t-shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Choose a variant')
        ->assertSee('In stock')
        ->assertPresent('[role="img"][aria-label="Classic Cotton T-Shirt"]')
        ->assertPresent('input[aria-label^="Select variant"]')
        ->assertNoJavaScriptErrors();
});

it('searches the published catalog and handles empty results', function (): void {
    $page = visit('/search');

    $page->fill('input[placeholder^="Try cotton"]', 'hoodie')
        ->waitForText('Organic Hoodie')
        ->assertDontSee('Unreleased Winter Jacket')
        ->fill('input[placeholder^="Try cotton"]', 'no-such-product-xyz')
        ->waitForText('No products found.')
        ->assertNoJavaScriptErrors();
});

it('does not expose draft or archived product detail pages', function (string $handle): void {
    visit('/products/'.$handle)
        ->assertSee('404')
        ->assertDontSee('Add to cart');
})->with(['unreleased-winter-jacket', 'discontinued-raincoat']);

it('renders published content pages', function (): void {
    visit('/pages/about')
        ->assertSee('About')
        ->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});
