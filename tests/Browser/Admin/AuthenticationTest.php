<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

function adminAuthenticationHost(): array
{
    return ['host' => 'shop.test'];
}

function adminAuthenticationLogin(): mixed
{
    return visit('/admin/login', adminAuthenticationHost())
        ->fill('input[type=email]', 'admin@acme.test')
        ->fill('input[type=password]', 'password')
        ->click('@admin-login-button')
        ->wait(1)
        ->assertPathIs('/admin')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
}

function adminAuthenticationSubmitWithoutBrowserRequired(string $email, string $password): mixed
{
    $page = visit('/admin/login', adminAuthenticationHost());

    $page->script('() => document.querySelectorAll("[required]").forEach((element) => element.removeAttribute("required"))');

    if ($email !== '') {
        $page->fill('input[type=email]', $email);
    }

    if ($password !== '') {
        $page->fill('input[type=password]', $password);
    }

    return $page->click('@admin-login-button')->wait(1);
}

test('can log in as admin', function (): void {
    adminAuthenticationLogin();
});

test('shows error for invalid credentials', function (): void {
    visit('/admin/login', adminAuthenticationHost())
        ->fill('input[type=email]', 'admin@acme.test')
        ->fill('input[type=password]', 'wrongpassword')
        ->click('@admin-login-button')
        ->wait(1)
        ->assertPathIs('/admin/login')
        ->assertSee('Invalid credentials')
        ->assertNoJavaScriptErrors();
});

test('shows error for empty email', function (): void {
    adminAuthenticationSubmitWithoutBrowserRequired('', 'password')
        ->assertPathIs('/admin/login')
        ->assertSee('email field is required')
        ->assertNoJavaScriptErrors();
});

test('shows error for empty password', function (): void {
    adminAuthenticationSubmitWithoutBrowserRequired('admin@acme.test', '')
        ->assertPathIs('/admin/login')
        ->assertSee('password field is required')
        ->assertNoJavaScriptErrors();
});

test('redirects unauthenticated users to login from dashboard', function (): void {
    visit('/admin', adminAuthenticationHost())
        ->wait(1)
        ->assertPathIs('/admin/login')
        ->assertSee('Sign in')
        ->assertNoJavaScriptErrors();
});

test('redirects unauthenticated users to login from products', function (): void {
    visit('/admin/products', adminAuthenticationHost())
        ->wait(1)
        ->assertPathIs('/admin/login')
        ->assertSee('Sign in')
        ->assertNoJavaScriptErrors();
});

test('can log out', function (): void {
    adminAuthenticationLogin()
        ->click('@sidebar-menu-button')
        ->click('button[data-test="logout-button"]:visible')
        ->wait(1)
        ->assertPathIs('/admin/login')
        ->assertSee('Sign in')
        ->assertNoJavaScriptErrors();
});

test('can navigate through admin sidebar sections', function (): void {
    $page = adminAuthenticationLogin();

    foreach ([
        '/admin/products' => 'Products',
        '/admin/orders' => 'Orders',
        '/admin/customers' => 'Customers',
        '/admin/discounts' => 'Discounts',
        '/admin/settings' => 'Store Settings',
    ] as $path => $heading) {
        $page->click("a[href$=\"{$path}\"]")
            ->wait(1)
            ->assertPathIs($path)
            ->assertSee($heading)
            ->assertNoJavaScriptErrors();
    }
});

test('can navigate to analytics from sidebar', function (): void {
    adminAuthenticationLogin()
        ->click('a[href$="/admin/analytics"]')
        ->wait(1)
        ->assertPathIs('/admin/analytics')
        ->assertSee('Analytics')
        ->assertNoJavaScriptErrors();
});

test('can navigate to themes from sidebar', function (): void {
    adminAuthenticationLogin()
        ->click('a[href$="/admin/themes"]')
        ->wait(1)
        ->assertPathIs('/admin/themes')
        ->assertSee('Themes')
        ->assertNoJavaScriptErrors();
});
