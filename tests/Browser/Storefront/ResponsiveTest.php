<?php

it('storefront home works on mobile viewport', function (): void {
    $page = visit('/')->resize(375, 812);

    $page->assertSee('Acme Fashion')
        ->assertVisible('button[aria-label="Open navigation menu"]')
        ->assertMissing('nav[aria-label="Main navigation"]')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth + 1')
        ->assertNoJavascriptErrors();
});

it('product page stacks layout on mobile', function (): void {
    $page = visit('/products/classic-cotton-t-shirt')->resize(375, 812);

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Add to cart')
        ->assertScript(
            "document.querySelector('section[aria-label=\"Product images\"]').getBoundingClientRect().bottom"
            ." <= document.querySelector('section[aria-label=\"Product information\"]').getBoundingClientRect().top + 1"
        )
        ->assertNoJavascriptErrors();
});

it('can add to cart on mobile', function (): void {
    $page = visit('/products/classic-cotton-t-shirt')->resize(375, 812);

    $page->click('M')
        ->click('label[title="Black"]')
        ->click('Add to cart')
        ->assertSee('Added to cart')
        ->assertNoJavascriptErrors();
});

it('cart page works on mobile', function (): void {
    $page = visit('/products/classic-cotton-t-shirt')->resize(375, 812);

    $page->click('M')
        ->click('label[title="Black"]')
        ->click('Add to cart')
        ->assertSee('Added to cart');

    $page->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertVisible('a:visible:has-text("Checkout")')
        ->assertNoJavascriptErrors();
});

it('checkout flow works on mobile', function (): void {
    $page = visit('/products/classic-cotton-t-shirt')->resize(375, 812);

    $page->click('M')
        ->click('label[title="Black"]')
        ->click('Add to cart')
        ->assertSee('Added to cart');

    $page->navigate('/cart')
        ->click('a:visible:has-text("Checkout")')
        ->assertSee('Contact information')
        ->fill('checkout-email', 'mobile@example.com')
        ->click('Continue');

    browserFillCheckoutAddress($page, 'Mobile', 'User', 'Mobile Str 1', 'Berlin', '10115', 'DE');

    $page->assertSee('Standard Shipping')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth + 1')
        ->assertNoJavascriptErrors();
});

it('admin login works on tablet viewport', function (): void {
    $page = visit('/admin/login')->resize(768, 1024);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('@admin-login-button')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

it('admin sidebar navigation works on tablet', function (): void {
    $page = visit('/admin/login')->resize(768, 1024);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('@admin-login-button')
        ->assertSee('Dashboard');

    $page->click('button[aria-label="Open sidebar"]')
        ->click('aside a:has-text("Products")')
        ->assertSeeIn('h1[data-flux-heading]', 'Products')
        ->assertNoJavascriptErrors()
        ->click('button[aria-label="Open sidebar"]')
        ->click('aside a:has-text("Orders")')
        ->assertSeeIn('h1[data-flux-heading]', 'Orders')
        ->assertNoJavascriptErrors();
});

it('collection page works on mobile with filters', function (): void {
    $page = visit('/collections/t-shirts')->resize(375, 812);

    $page->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertVisible('button:visible:has-text("Filter")')
        ->assertNoJavascriptErrors();
});
