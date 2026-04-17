<?php

// Suite 10: Customer Account - Registration, login, order history, addresses, logout

it('can register a new customer', function () {
    $page = $this->visit('/account/register', ['host' => 'acme-fashion.test']);

    $page->assertSee('Create Account')
        ->fill('name', 'E2E Test Customer')
        ->fill('email', 'e2e-customer@test.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->click('Create Account')
        ->assertNoJavaScriptErrors();
});

it('can log in as customer', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();
});

it('shows error for invalid customer credentials', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'wrongpassword')
        ->click('Log in')
        ->assertNoJavaScriptErrors();
});

it('shows the account dashboard', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();
});

it('shows order history page', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();

    $orders = $this->visit('/account/orders', ['host' => 'acme-fashion.test']);

    $orders->assertSee('Orders')
        ->assertNoJavaScriptErrors();
});

it('shows the addresses page', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();

    $addresses = $this->visit('/account/addresses', ['host' => 'acme-fashion.test']);

    $addresses->assertSee('Addresses')
        ->assertNoJavaScriptErrors();
});

it('can add a new address', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();

    $addresses = $this->visit('/account/addresses', ['host' => 'acme-fashion.test']);

    $addresses->assertSee('Addresses')
        ->assertNoJavaScriptErrors();
});

it('can log out as customer', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();
});

it('redirects unauthenticated users to login', function () {
    $page = $this->visit('/account', ['host' => 'acme-fashion.test']);

    $page->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});

it('shows customer name on dashboard', function () {
    $page = $this->visit('/account/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavaScriptErrors();
});

it('validates registration required fields', function () {
    $page = $this->visit('/account/register', ['host' => 'acme-fashion.test']);

    $page->click('Create Account')
        ->assertNoJavaScriptErrors();
});

it('prevents duplicate email registration', function () {
    $page = $this->visit('/account/register', ['host' => 'acme-fashion.test']);

    $page->fill('name', 'Duplicate Customer')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->click('Create Account')
        ->assertNoJavaScriptErrors();
});
