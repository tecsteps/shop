<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

test('store 1 only shows store 1 products', function () {
    visit('/')
        ->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Acme Electronics')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->assertDontSee('Mechanical Keyboard')
        ->assertNoJavascriptErrors();
});

test('store 1 collections only contain store 1 products', function () {
    visit('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->assertDontSee('USB-C Cable 2m')
        ->assertNoJavascriptErrors();
});

test('admin cannot access other store data', function () {
    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());

    visit('/admin/products')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Mechanical Keyboard')
        ->assertDontSee('Monitor Stand')
        ->assertNoJavascriptErrors();

    visit('/admin/orders')
        ->assertSee('#1001')
        ->assertDontSee('#5001')
        ->assertDontSee('#5002')
        ->assertDontSee('#5003')
        ->assertNoJavascriptErrors();
});

test('search only returns current store products', function () {
    // "shirt" (instead of the spec'd "product") because the FTS index yields
    // no hits for "product", which would make the isolation check vacuous.
    visit('/search?q=shirt')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('USB-C Cable 2m')
        ->assertDontSee('Mechanical Keyboard')
        ->assertNoJavascriptErrors();
});

test('customer accounts are scoped to their store', function () {
    $customer = Customer::query()->where('email', 'customer@acme.test')->sole();

    $this->actingAs($customer, 'customer');

    visit('/account/orders')
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004')
        ->assertDontSee('#5001')
        ->assertDontSee('#5002')
        ->assertDontSee('#5003')
        ->assertNoJavascriptErrors();
});
