<?php

use App\Models\CartLine;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

/**
 * Add one Classic Cotton T-Shirt (Size M / Color Black) to the session cart
 * through the product page.
 */
function responsiveTestAddTshirtToCart($page): void
{
    $page
        ->press('M')
        ->wait(1)
        ->press('button[aria-label="Black"]')
        ->wait(1)
        ->press('Add to cart')
        ->wait(1);
}

test('storefront home works on mobile viewport', function () {
    visit('/')
        ->resize(375, 812)
        ->assertSee('Acme Fashion')
        // Mobile hamburger is visible, desktop navigation is hidden.
        ->assertVisible('button[aria-label="Open navigation menu"]')
        ->assertMissing('nav[aria-label="Main navigation"]')
        // No horizontal scrolling.
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        // The hamburger opens the mobile navigation drawer (the outer wrapper
        // has only fixed children, so assert the visible panel instead).
        ->press('button[aria-label="Open navigation menu"]')
        ->wait(1)
        ->assertVisible('#mobile-navigation .fixed.inset-y-0')
        ->assertNoJavascriptErrors();
});

test('product page stacks layout on mobile', function () {
    visit('/products/classic-cotton-t-shirt')
        ->resize(375, 812)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Add to cart')
        // Gallery sits above the product title: single-column stacked layout.
        ->assertScript(
            '(() => {
                const gallery = document.querySelector(\'section[aria-label="Product images"]\').getBoundingClientRect();
                const title = document.querySelector(\'h1\').getBoundingClientRect();

                return gallery.bottom <= title.top + 2;
            })()'
        )
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertNoJavascriptErrors();
});

test('can add to cart on mobile', function () {
    $page = visit('/products/classic-cotton-t-shirt')->resize(375, 812);

    responsiveTestAddTshirtToCart($page);

    $page->assertNoJavascriptErrors();

    expect(CartLine::query()->count())->toBe(1);
});

test('cart page works on mobile', function () {
    $page = visit('/products/classic-cotton-t-shirt')->resize(375, 812);

    responsiveTestAddTshirtToCart($page);

    $page
        ->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertVisible('main button:has-text("Checkout")')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertNoJavascriptErrors();
});

test('checkout flow works on mobile', function () {
    $page = visit('/products/classic-cotton-t-shirt')->resize(375, 812);

    responsiveTestAddTshirtToCart($page);

    $page
        ->navigate('/cart')
        ->press('main button:has-text("Checkout")')
        ->wait(1)
        ->assertPathIs('/checkout/new')
        ->fill('checkout-email', 'mobile@example.com')
        ->fill('address-first_name', 'Mobile')
        ->fill('address-last_name', 'User')
        ->fill('address-address1', 'Mobile Str 1')
        ->fill('address-city', 'Berlin')
        ->fill('address-postal_code', '10115')
        ->fill('address-country_code', 'DE')
        ->press('button:has-text("Continue to shipping")')
        ->wait(1)
        ->waitForText('Standard Shipping')
        ->assertSee('Standard Shipping')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertNoJavascriptErrors();
});

test('admin login works on tablet viewport', function () {
    visit('/admin/login')
        ->resize(768, 1024)
        ->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->press('button[type="submit"]')
        ->waitForText('Dashboard')
        ->assertPathIs('/admin')
        ->assertNoJavascriptErrors();
});

test('admin sidebar navigation works on tablet', function () {
    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());

    visit('/admin')
        ->resize(768, 1024)
        // Below lg the sidebar is a slide-over behind the hamburger.
        ->press('button[aria-label="Open navigation menu"]')
        ->wait(1)
        ->assertVisible('aside[aria-label="Admin navigation"]')
        ->click('aside a:has-text("Products")')
        ->wait(1)
        ->assertPathIs('/admin/products')
        ->assertVisible('main .text-2xl[data-flux-heading]:has-text("Products")')
        ->press('button[aria-label="Open navigation menu"]')
        ->wait(1)
        ->click('aside a:has-text("Orders")')
        ->wait(1)
        ->assertPathIs('/admin/orders')
        ->assertVisible('main .text-2xl[data-flux-heading]:has-text("Orders")')
        ->assertNoJavascriptErrors();
});

test('collection page works on mobile with filters', function () {
    visit('/collections/t-shirts')
        ->resize(375, 812)
        ->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        // Filters live behind a toggle button on mobile.
        ->assertVisible('button[aria-label="Toggle filters"]')
        ->press('button[aria-label="Toggle filters"]')
        ->wait(1)
        ->assertVisible('div[role="dialog"][aria-label="Filters"] .fixed.inset-y-0')
        ->assertNoJavascriptErrors();
});
