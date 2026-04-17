<?php

// Suite 2: Admin Authentication - Login, logout, invalid credentials, session access control

it('can log in as admin', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});

it('shows error for invalid credentials', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'wrongpassword')
        ->click('Log in')
        ->assertSee('Invalid credentials')
        ->assertNoJavaScriptErrors();
});

it('shows error for empty email', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('password', 'password')
        ->click('Log in')
        ->assertSee('email')
        ->assertNoJavaScriptErrors();
});

it('shows error for empty password', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->click('Log in')
        ->assertSee('password')
        ->assertNoJavaScriptErrors();
});

it('redirects unauthenticated users to login from dashboard', function () {
    $page = $this->visit('/admin', ['host' => 'acme-fashion.test']);

    $page->assertSee('Admin Login')
        ->assertNoJavaScriptErrors();
});

it('redirects unauthenticated users to login from products', function () {
    $page = $this->visit('/admin/products', ['host' => 'acme-fashion.test']);

    $page->assertSee('Admin Login')
        ->assertNoJavaScriptErrors();
});

it('can log out', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard')
        ->wait(0.5)
        ->click('button[data-flux-profile]')
        ->wait(0.5)
        ->click('Log out')
        ->assertSee('Admin Login');
});

it('can navigate through admin sidebar sections', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard')
        ->click('a:has-text("Products")')
        ->assertSee('Add product')
        ->assertNoJavaScriptErrors()
        ->click('a:has-text("Orders")')
        ->assertSee('Orders')
        ->assertNoJavaScriptErrors()
        ->click('a:has-text("Customers")')
        ->assertSee('Customers')
        ->assertNoJavaScriptErrors()
        ->click('a:has-text("Discounts")')
        ->assertSee('Discounts')
        ->assertNoJavaScriptErrors()
        ->click('nav a:has-text("Settings")')
        ->assertSee('Settings')
        ->assertNoJavaScriptErrors();
});

it('can navigate to analytics from sidebar', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard')
        ->click('a:has-text("Analytics")')
        ->assertSee('Analytics')
        ->assertNoJavaScriptErrors();
});

it('can navigate to themes from sidebar', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertSee('Dashboard')
        ->click('a:has-text("Themes")')
        ->assertSee('Themes')
        ->assertNoJavaScriptErrors();
});
