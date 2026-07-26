<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

test('shows the product list with seeded products', function () {
    // The list paginates 15 per page (20 seeded products) sorted by
    // updated_at desc with identical seed timestamps, so the seeded products
    // are located through the list's own search box for determinism.
    visit('/admin/products')
        ->assertSee('Add product')
        ->fill('search', 'Classic Cotton')
        ->waitForText('Classic Cotton T-Shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->fill('search', 'Premium Slim')
        ->waitForText('Premium Slim Fit Jeans')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavascriptErrors();
});

test('can create a new product', function () {
    $page = visit('/admin/products');

    // The variant price input expects integer cents, so 29.99 EUR is 2999.
    $page->press('main a:has-text("Add product")')
        ->waitForText('Organization')
        ->fill('title', 'Test Product Created by E2E')
        ->fill('descriptionHtml', 'This product was created by the E2E test suite.')
        ->fill('vendor', 'Test Vendor')
        ->fill('productType', 'T-Shirts')
        ->fill('[aria-label="Price in cents"]', '2999')
        ->fill('[aria-label="SKU"]', 'E2E-TEST-001')
        ->fill('[aria-label="Quantity on hand"]', '50')
        ->press('button:has-text("Save")')
        ->waitForText('Product saved')
        ->assertSee('Product saved')
        ->assertNoJavascriptErrors();

    // Fresh visit instead of the sidebar link: wire:navigate may restore a
    // cached snapshot of the list that predates the creation.
    visit('/admin/products')
        ->waitForText('Test Product Created by E2E')
        ->assertSee('Test Product Created by E2E')
        ->assertNoJavascriptErrors();
});

test('can edit an existing product title', function () {
    $page = visit('/admin/products');

    $page->fill('search', 'Classic Cotton')
        ->waitForText('Classic Cotton T-Shirt')
        ->press('table a:has-text("Classic Cotton T-Shirt")')
        ->waitForText('Organization')
        ->fill('title', 'Classic Cotton T-Shirt Updated')
        ->press('button:has-text("Save")')
        ->waitForText('Product saved')
        ->assertSee('Product saved')
        ->assertNoJavascriptErrors();

    // Fresh visit instead of the sidebar link: wire:navigate may restore a
    // cached snapshot of the list that predates the rename.
    visit('/admin/products')
        ->waitForText('Classic Cotton T-Shirt Updated')
        ->assertSee('Classic Cotton T-Shirt Updated')
        ->assertNoJavascriptErrors();
});

test('can archive a product', function () {
    $page = visit('/admin/products');

    // The variant price input expects integer cents, so 19.99 EUR is 1999.
    $page->press('main a:has-text("Add product")')
        ->waitForText('Organization')
        ->fill('title', 'Product To Archive')
        ->fill('[aria-label="Price in cents"]', '1999')
        ->fill('[aria-label="SKU"]', 'E2E-ARCHIVE-001')
        ->fill('[aria-label="Quantity on hand"]', '10')
        ->press('button:has-text("Save")')
        ->waitForText('Product saved')
        ->assertSee('Product saved');

    // Fresh visits instead of the sidebar link: wire:navigate may restore a
    // cached snapshot of the list that predates the creation/archival.
    // The list defaults to the "All" filter (spec assumed "Active"), so the
    // archived product must disappear from the Active tab and show up under
    // the Archived tab instead.
    $page = visit('/admin/products');

    $page->waitForText('Product To Archive')
        ->press('table a:has-text("Product To Archive")')
        ->waitForText('Organization')
        ->select('status', 'archived')
        ->keys('#status', ['Tab']) // trigger the blur sync of wire:model.blur
        ->press('button:has-text("Save")')
        ->waitForText('Product saved')
        ->assertSee('Product saved')
        ->assertNoJavascriptErrors();

    $page = visit('/admin/products');

    $page->waitForText('Add product')
        ->press('button[role="tab"]:has-text("Active")')
        ->wait(1)
        ->assertDontSee('Product To Archive')
        ->press('button[role="tab"]:has-text("Archived")')
        ->waitForText('Product To Archive')
        ->assertSee('Product To Archive')
        ->assertNoJavascriptErrors();
});

test('shows draft products only in admin not storefront', function () {
    $page = visit('/admin/products');

    // Draft product #15 shows in the admin list with its "Draft" badge.
    $page->fill('search', 'Unreleased')
        ->waitForText('Unreleased Winter Jacket')
        ->assertVisible('table tr:has-text("Unreleased Winter Jacket"):has-text("Draft")')
        ->assertNoJavascriptErrors();

    // ...but is absent from the storefront collection listing.
    visit('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();

    // ...and absent from storefront search results.
    visit('/search?q=Unreleased')
        ->assertSee('No results found')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();
});

test('can search products in admin', function () {
    visit('/admin/products')
        ->fill('search', 'Cotton')
        ->waitForText('Classic Cotton T-Shirt')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Premium Slim Fit Jeans')
        ->assertNoJavascriptErrors();
});

test('can filter products by status in admin', function () {
    $page = visit('/admin/products');

    $page->press('button[role="tab"]:has-text("Draft")')
        ->wait(1)
        ->assertSee('Unreleased Winter Jacket')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavascriptErrors();

    // Search within the Active tab: with 18 active products on a 15-per-page
    // list, this keeps the assertion independent of sort order.
    $page->press('button[role="tab"]:has-text("Active")')
        ->fill('search', 'Classic Cotton')
        ->waitForText('Classic Cotton T-Shirt')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavascriptErrors();
});
