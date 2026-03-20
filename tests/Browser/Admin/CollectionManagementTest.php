<?php

// Suite 15: Admin Collections - Collection CRUD in admin

it('shows collection list', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Collections")')
        ->assertSee('T-Shirts')
        ->assertSee('New Arrivals')
        ->assertNoJavaScriptErrors();
});

it('can create a new collection', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Collections")')
        ->click('New collection')
        ->fill('title', 'E2E Test Collection')
        ->click('Create collection')
        ->assertSee('Collection created')
        ->assertNoJavaScriptErrors();
});

it('can edit a collection title', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Collections")')
        ->click('T-Shirts')
        ->clear('title')
        ->fill('title', 'T-Shirts Updated')
        ->click('Save changes')
        ->assertSee('Collection updated')
        ->assertNoJavaScriptErrors();
});
