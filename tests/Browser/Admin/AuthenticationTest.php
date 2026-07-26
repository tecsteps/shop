<?php

use Database\Seeders\DatabaseSeeder;
use Pest\Browser\Api\AwaitableWebpage;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

if (! function_exists('adminAuthenticationFormLogin')) {
    /**
     * Perform the full admin login sequence through the login form. Spec 08
     * suite 2 requires each test to log in via the UI for independence.
     */
    function adminAuthenticationFormLogin(): AwaitableWebpage
    {
        return visit('/admin/login')
            ->fill('email', 'admin@acme.test')
            ->fill('password', 'password')
            ->press('button[type="submit"]')
            ->waitForText('Dashboard');
    }
}

test('can log in as admin', function () {
    visit('/admin/login')
        ->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->press('button[type="submit"]')
        ->waitForText('Dashboard')
        ->assertPathIs('/admin')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

test('shows error for invalid credentials', function () {
    visit('/admin/login')
        ->fill('email', 'admin@acme.test')
        ->fill('password', 'wrongpassword')
        ->press('button[type="submit"]')
        ->waitForText('Invalid credentials')
        ->assertPathIs('/admin/login')
        ->assertSee('Invalid credentials')
        ->assertNoJavascriptErrors();
});

test('shows error for empty email', function () {
    visit('/admin/login')
        ->fill('password', 'password')
        ->press('button[type="submit"]')
        ->waitForText('The email field is required')
        ->assertSee('The email field is required')
        ->assertNoJavascriptErrors();
});

test('shows error for empty password', function () {
    visit('/admin/login')
        ->fill('email', 'admin@acme.test')
        ->press('button[type="submit"]')
        ->waitForText('The password field is required')
        ->assertSee('The password field is required')
        ->assertNoJavascriptErrors();
});

test('redirects unauthenticated users to login from dashboard', function () {
    visit('/admin')
        ->assertPathIs('/admin/login')
        ->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

test('redirects unauthenticated users to login from products', function () {
    visit('/admin/products')
        ->assertPathIs('/admin/login')
        ->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

test('can log out', function () {
    $page = adminAuthenticationFormLogin();

    $page->assertSee('Dashboard')
        ->press('button[data-flux-profile]')
        ->click('Log out')
        ->waitForText('Sign in')
        ->assertPathIs('/admin/login')
        ->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

test('can navigate through admin sidebar sections', function () {
    $page = adminAuthenticationFormLogin();

    $page->press('nav[aria-label="Admin"] a:has-text("Products")')
        ->waitForText('Add product')
        ->assertPathIs('/admin/products')
        ->assertSee('Products')
        ->assertNoJavascriptErrors();

    $page->press('nav[aria-label="Admin"] a:has-text("Orders")')
        ->waitForText('#1001')
        ->assertPathIs('/admin/orders')
        ->assertSee('Orders')
        ->assertNoJavascriptErrors();

    $page->press('nav[aria-label="Admin"] a:has-text("Customers")')
        ->waitForText('customer@acme.test')
        ->assertPathIs('/admin/customers')
        ->assertSee('Customers')
        ->assertNoJavascriptErrors();

    $page->press('nav[aria-label="Admin"] a:has-text("Discounts")')
        ->waitForText('WELCOME10')
        ->assertPathIs('/admin/discounts')
        ->assertSee('Discounts')
        ->assertNoJavascriptErrors();

    $page->press('nav[aria-label="Admin"] a:has-text("Settings")')
        ->waitForText('Store details')
        ->assertPathIs('/admin/settings')
        ->assertSee('Settings')
        ->assertNoJavascriptErrors();
});

test('can navigate to analytics from sidebar', function () {
    $page = adminAuthenticationFormLogin();

    $page->press('nav[aria-label="Admin"] a:has-text("Analytics")')
        ->waitForText('Sales over time')
        ->assertPathIs('/admin/analytics')
        ->assertSee('Analytics')
        ->assertNoJavascriptErrors();
});

test('can navigate to themes from sidebar', function () {
    $page = adminAuthenticationFormLogin();

    $page->press('nav[aria-label="Admin"] a:has-text("Themes")')
        ->waitForText('Add theme')
        ->assertPathIs('/admin/themes')
        ->assertSee('Themes')
        ->assertNoJavascriptErrors();
});
