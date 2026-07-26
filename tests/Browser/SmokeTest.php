<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

test('storefront home page loads without javascript errors', function () {
    visit('/')
        ->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavascriptErrors();
});

test('admin dashboard loads for an authenticated owner', function () {
    $admin = User::query()->where('email', 'admin@acme.test')->sole();

    actingAsAdmin($admin);

    visit('/admin')
        ->assertSee('Dashboard')
        ->assertSee('Total Sales')
        ->assertNoJavascriptErrors();
});

test('admin login page signs in with valid credentials', function () {
    visit('/admin/login')
        ->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->press('button[type="submit"]')
        ->waitForText('Dashboard')
        ->assertPathIs('/admin');
});
