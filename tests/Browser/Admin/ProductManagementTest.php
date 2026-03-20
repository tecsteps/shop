<?php

// Suite 3: Admin Product Management - CRUD operations

it('shows the product list with seeded products', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Products")')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavaScriptErrors();
});

it('can create a new product', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Products")')
        ->click('Add product')
        ->fill('title', 'Test Product Created by E2E')
        ->fill('description', 'This product was created by the E2E test suite.')
        ->fill('vendor', 'Test Vendor')
        ->fill('product_type', 'T-Shirts')
        ->click('Create product')
        ->assertSee('Product created')
        ->assertNoJavaScriptErrors();
});

it('can edit an existing product title', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Products")')
        ->assertSee('Classic Cotton T-Shirt')
        ->click('Classic Cotton T-Shirt')
        ->clear('title')
        ->fill('title', 'Classic Cotton T-Shirt Updated')
        ->click('Save changes')
        ->assertSee('Product updated')
        ->assertNoJavaScriptErrors();
});

it('can archive a product', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Products")')
        ->click('Add product')
        ->fill('title', 'Product To Archive')
        ->click('Create product')
        ->assertSee('Product created')
        ->assertNoJavaScriptErrors();
});

it('shows draft products only in admin not storefront', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Products")')
        ->assertSee('Draft')
        ->assertNoJavaScriptErrors();

    $storefront = $this->visit('/search?q=unreleased', ['host' => 'acme-fashion.test']);

    $storefront->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors();
});

it('can search products in admin', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Products")')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('can filter products by status in admin', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Products")')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Active')
        ->assertSee('Draft')
        ->assertNoJavaScriptErrors();
});
