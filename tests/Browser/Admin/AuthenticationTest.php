<?php

it('can log in as admin', function (): void {
    $page = visit('/admin/login');

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('@admin-login-button')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

it('shows error for invalid credentials', function (): void {
    $page = visit('/admin/login');

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'wrongpassword')
        ->click('@admin-login-button')
        ->assertSee('Invalid credentials')
        ->assertNoJavascriptErrors();
});

it('shows error for empty email', function (): void {
    $page = visit('/admin/login');

    $page->fill('password', 'password')
        ->click('@admin-login-button')
        ->assertSee('The email field is required')
        ->assertNoJavascriptErrors();
});

it('shows error for empty password', function (): void {
    $page = visit('/admin/login');

    $page->fill('email', 'admin@acme.test')
        ->click('@admin-login-button')
        ->assertSee('The password field is required')
        ->assertNoJavascriptErrors();
});

it('redirects unauthenticated users to login from dashboard', function (): void {
    $page = visit('/admin');

    $page->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

it('redirects unauthenticated users to login from products', function (): void {
    $page = visit('/admin/products');

    $page->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

it('can log out', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('@admin-user-menu')
        ->click('@admin-logout-button')
        ->assertSee('Sign in');
});

it('can navigate through admin sidebar sections', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Products")')
        ->assertSeeIn('h1[data-flux-heading]', 'Products')
        ->assertNoJavascriptErrors()
        ->click('aside a:has-text("Orders")')
        ->assertSeeIn('h1[data-flux-heading]', 'Orders')
        ->assertNoJavascriptErrors()
        ->click('aside a:has-text("Customers")')
        ->assertSeeIn('h1[data-flux-heading]', 'Customers')
        ->assertNoJavascriptErrors()
        ->click('aside a:has-text("Discounts")')
        ->assertSeeIn('h1[data-flux-heading]', 'Discounts')
        ->assertNoJavascriptErrors()
        ->click('aside a:has-text("Settings")')
        ->assertSeeIn('h1[data-flux-heading]', 'Store Settings')
        ->assertNoJavascriptErrors();
});

it('can navigate to analytics from sidebar', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Analytics")')
        ->assertSeeIn('h1[data-flux-heading]', 'Analytics')
        ->assertNoJavascriptErrors();
});

it('can navigate to themes from sidebar', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Themes")')
        ->assertSeeIn('h1[data-flux-heading]', 'Themes')
        ->assertNoJavascriptErrors();
});
