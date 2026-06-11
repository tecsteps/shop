<?php

it('store 1 only shows store 1 products', function (): void {
    $page = visit('/');

    $page->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->assertNoJavascriptErrors();

    // Re-point the hostname at Acme Electronics and verify the reverse
    // direction through the real ResolveStore middleware: store 2 never
    // shows store 1 data.
    switchBrowserTestDomainToStore('acme-electronics');

    $electronicsPage = visit('/');

    $electronicsPage->assertSee('Acme Electronics')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertDontSee('Acme Fashion')
        ->assertNoJavascriptErrors();
});

it('store 1 collections only contain store 1 products', function (): void {
    $page = visit('/collections/t-shirts');

    $page->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->assertDontSee('Mechanical Keyboard')
        ->assertNoJavascriptErrors();
});

it('admin cannot access other store data', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->assertSeeIn('h1[data-flux-heading]', 'Products')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->fill('@product-search', 'Laptop')
        ->wait(1)
        ->assertSee('No products match your filters.')
        ->assertNoJavascriptErrors();

    $page->click('aside a:has-text("Orders")')
        ->assertSeeIn('h1[data-flux-heading]', 'Orders')
        ->assertSee('#1001')
        ->assertDontSee('#5001')
        ->fill('@order-search', '#5001')
        ->wait(1)
        ->assertSee('No orders match your filters.')
        ->assertNoJavascriptErrors();
});

it('search only returns current store products', function (): void {
    $page = visit('/search?q=product');

    $page->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->assertNoJavascriptErrors();

    $page->navigate('/search?q=laptop')
        ->assertSee('No results')
        ->assertDontSee('Pro Laptop 15')
        ->assertNoJavascriptErrors();
});

it('customer accounts are scoped to their store', function (): void {
    $page = browserLoginAsCustomer();

    $page->click('Orders')
        ->assertSee('Order History')
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004')
        ->assertDontSee('#5001')
        ->assertDontSee('#5002')
        ->assertNoJavascriptErrors();
});
