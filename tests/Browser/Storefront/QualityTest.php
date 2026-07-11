<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

it('keeps the second tenant catalog out of the fashion storefront', function (): void {
    visit('/search?q=Pro%20Laptop')
        ->assertSee('No products found.')
        ->assertDontSee('Pro Laptop 15')
        ->navigate('/products/pro-laptop-15')
        ->assertSee('404');
});

it('renders storefront essentials at a mobile viewport', function (): void {
    visit('/')
        ->resize(390, 844)
        ->assertVisible('body > header')
        ->assertSee('Find your next favorite.')
        ->assertSee('Featured products')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertNoJavaScriptErrors();
});

it('supports a mobile product-to-cart interaction', function (): void {
    visit('/products/classic-cotton-t-shirt')
        ->resize(390, 844)
        ->assertVisible('h1:first-of-type')
        ->press('Add to cart')
        ->waitForText('Shopping cart')
        ->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('provides landmark, heading, and labeled control semantics', function (): void {
    visit('/products/classic-cotton-t-shirt')
        ->assertPresent('body > header')
        ->assertPresent('body > main')
        ->assertPresent('body > footer')
        ->assertCount('h1:first-of-type', 1)
        ->assertPresent('input[aria-label^="Select variant"]')
        ->assertPresent('input[type="number"]')
        ->assertPresent('[aria-live="polite"]')
        ->assertNoJavaScriptErrors();
});
