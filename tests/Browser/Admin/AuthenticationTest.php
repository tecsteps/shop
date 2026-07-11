<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

it('redirects guests away from protected admin pages', function (): void {
    visit('/admin/products')
        ->assertPathIs('/admin/login')
        ->assertSee('Access your store administration.')
        ->assertNoJavaScriptErrors();
});

it('rejects invalid admin credentials', function (): void {
    visit('/admin/login')
        ->fill('email', 'admin@acme.test')
        ->fill('password', 'wrong-password')
        ->click('form button[type="submit"]')
        ->waitForText('Invalid credentials.')
        ->assertPathIs('/admin/login')
        ->assertNoJavaScriptErrors();
});

it('signs an owner into the selected store dashboard', function (): void {
    loginBrowserAdmin()
        ->assertPathIs('/admin')
        ->assertSee('Total sales')
        ->assertSee('Orders')
        ->assertSee('Top products')
        ->assertNoJavaScriptErrors();
});

it('redirects an authenticated owner away from the admin login', function (): void {
    loginBrowserAdmin()
        ->navigate('/admin/login')
        ->assertPathIs('/admin')
        ->assertSee('Total sales')
        ->assertNoJavaScriptErrors();
});
