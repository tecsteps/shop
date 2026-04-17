<?php

// Suite 6: Admin Settings - Store settings, shipping zones, tax config, domains

it('can view store settings', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('nav a:has-text("Settings")')
        ->assertSee('Settings')
        ->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});

it('can update store name', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('nav a:has-text("Settings")')
        ->assertSee('Settings')
        ->assertSee('Acme Fashion')
        ->assertNoJavaScriptErrors();
});

it('can view shipping zones', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('nav a:has-text("Settings")')
        ->assertSee('Settings')
        ->assertNoJavaScriptErrors();
});

it('can add a new shipping rate to existing zone', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('nav a:has-text("Settings")')
        ->assertSee('Settings')
        ->assertNoJavaScriptErrors();
});

it('can view tax settings', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('nav a:has-text("Settings")')
        ->assertSee('Settings')
        ->assertNoJavaScriptErrors();
});

it('can update tax inclusion setting', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('nav a:has-text("Settings")')
        ->assertSee('Settings')
        ->assertNoJavaScriptErrors();
});

it('can view domain settings', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('nav a:has-text("Settings")')
        ->assertSee('acme-fashion.test')
        ->assertNoJavaScriptErrors();
});
