<?php

it('shows the product list with seeded products', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->assertSeeIn('h1[data-flux-heading]', 'Products')
        ->click('thead button:has-text("Title")')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavascriptErrors();
});

it('can create a new product', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->assertSeeIn('h1[data-flux-heading]', 'Products')
        ->click('@add-product-button')
        ->assertSeeIn('h1[data-flux-heading]', 'Add product')
        ->fill('title', 'Test Product Created by E2E')
        ->fill('@product-description-input', 'This product was created by the E2E test suite.')
        ->fill('vendor', 'Test Vendor')
        ->fill('productType', 'T-Shirts')
        ->fill('@variant-price-0', '29.99')
        ->fill('[name="variants.0.sku"]', 'E2E-TEST-001')
        ->fill('@variant-quantity-0', '50')
        ->click('@save-product-button')
        ->assertSee('Product saved')
        ->assertNoJavascriptErrors()
        ->click('aside a:has-text("Products")')
        ->assertSee('Test Product Created by E2E');
});

it('can edit an existing product title', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->fill('@product-search', 'Classic Cotton')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->click('Classic Cotton T-Shirt')
        ->assertSeeIn('h1[data-flux-heading]', 'Classic Cotton T-Shirt')
        ->fill('title', 'Classic Cotton T-Shirt Updated')
        ->click('@save-product-button')
        ->assertSee('Product saved')
        ->assertNoJavascriptErrors()
        ->click('aside a:has-text("Products")')
        ->assertSee('Classic Cotton T-Shirt Updated');
});

it('can archive a product', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->click('@add-product-button')
        ->fill('title', 'Product To Archive')
        ->fill('@variant-price-0', '19.99')
        ->fill('[name="variants.0.sku"]', 'E2E-ARCHIVE-001')
        ->fill('@variant-quantity-0', '10')
        ->click('@save-product-button')
        ->assertSee('Product saved')
        ->click('aside a:has-text("Products")')
        ->assertSee('Product To Archive')
        ->click('Product To Archive')
        ->select('@product-status-select', 'archived')
        ->click('@save-product-button')
        ->assertSee('Product saved')
        ->click('aside a:has-text("Products")')
        ->click('button[role="tab"]:has-text("Active")')
        ->assertDontSee('Product To Archive')
        ->assertNoJavascriptErrors();
});

it('shows draft products only in admin, not storefront', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->assertSee('Unreleased Winter Jacket')
        ->assertSeeIn('table tr:has-text("Unreleased Winter Jacket")', 'Draft')
        ->assertNoJavascriptErrors();

    $page->navigate('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();

    $page->navigate('/search?q=draft')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();
});

it('can search products in admin', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->fill('@product-search', 'Cotton')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Premium Slim Fit Jeans')
        ->assertNoJavascriptErrors();
});

it('can filter products by status in admin', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->click('@product-status-tab-draft')
        ->assertVisible('[data-test="product-status-tab-draft"][aria-selected="true"]')
        ->assertSee('Unreleased Winter Jacket')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavascriptErrors()
        ->click('@product-status-tab-active')
        ->assertVisible('[data-test="product-status-tab-active"][aria-selected="true"]')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();
});
