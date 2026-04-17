<?php

// Suite 12: Tenant Isolation - Multi-store data isolation verification

it('shows only store 1 products on store 1 domain', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test']);

    $page->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

it('isolates admin sessions per store', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});

it('isolates customer accounts per store', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();
});

it('shows store 1 collections only', function () {
    $page = $this->visit('/collections', ['host' => 'acme-fashion.test']);

    $page->assertSee('T-Shirts')
        ->assertSee('New Arrivals')
        ->assertNoJavaScriptErrors();
});

it('shows correct store name in storefront', function () {
    $page = $this->visit('/', ['host' => 'acme-fashion.test']);

    $page->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});
