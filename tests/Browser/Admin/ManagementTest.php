<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

it('navigates the seeded product catalog and filters products', function (): void {
    loginBrowserAdmin()
        ->navigate('/admin/products')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Unreleased Winter Jacket')
        ->fill('input[placeholder="Search products…"]', 'Winter Jacket')
        ->waitForText('Unreleased Winter Jacket')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('creates a draft product with inventory', function (): void {
    loginBrowserAdmin()
        ->navigate('/admin/products/create')
        ->fill('title', 'Browser Test Cap')
        ->fill('descriptionHtml', 'Created through the acceptance suite.')
        ->fill('[name="variants.0.sku"]', 'BROWSER-CAP-1')
        ->fill('[name="variants.0.price"]', '2999')
        ->fill('[name="variants.0.quantity"]', '12')
        ->click('form button[type="submit"]')
        ->waitForText('Product saved.')
        ->navigate('/admin/products')
        ->assertSee('Browser Test Cap')
        ->assertNoJavaScriptErrors();
});

it('shows order, customer, collection, discount, and page management', function (): void {
    $page = loginBrowserAdmin();

    $page->navigate('/admin/orders')->assertSee('#1001')->assertSee('customer@acme.test');
    $page->navigate('/admin/customers')->assertSee('John Doe')->assertSee('customer@acme.test');
    $page->navigate('/admin/collections')->assertSee('T-Shirts')->assertSee('New Arrivals');
    $page->navigate('/admin/discounts')->assertSee('WELCOME10')->assertSee('FREESHIP');
    $page->navigate('/admin/pages')->assertSee('About')->assertSee('Shipping & Returns');
    $page->assertNoJavaScriptErrors();
});

it('renders analytics and operational settings', function (): void {
    $page = loginBrowserAdmin();

    $page->navigate('/admin/analytics')->assertSee('Analytics')->assertSee('Revenue');
    $page->navigate('/admin/settings')->assertSee('Store Settings')->assertSee('Acme Fashion');
    $page->navigate('/admin/settings/domains')->assertSee('acme-fashion.test');
    $page->navigate('/admin/settings/shipping')->assertSee('Domestic')->assertSee('Standard Shipping');
    $page->navigate('/admin/settings/taxes')->assertSee('Tax Settings');
    $page->assertNoJavaScriptErrors();
});

it('renders the admin dashboard at tablet size', function (): void {
    loginBrowserAdmin()
        ->resize(768, 1024)
        ->assertSee('Dashboard')
        ->assertSee('Total sales')
        ->assertPresent('nav[aria-label="Admin navigation"]')
        ->assertNoJavaScriptErrors();
});
