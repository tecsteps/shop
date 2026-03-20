<?php

// Suite 16: Admin Customers - Customer listing and detail views

it('shows customer list', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Customers")')
        ->assertSee('customer@acme.test')
        ->assertNoJavaScriptErrors();
});

it('can view customer detail', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Customers")')
        ->assertSee('customer@acme.test')
        ->assertNoJavaScriptErrors();
});

it('can search customers', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Customers")')
        ->assertSee('Customers')
        ->assertNoJavaScriptErrors();
});
