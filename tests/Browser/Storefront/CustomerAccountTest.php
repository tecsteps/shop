<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

/**
 * Log the seeded storefront customer in through the browser session.
 */
function customerAccountTestLoginAsSeededCustomer(): Customer
{
    $customer = Customer::query()->where('email', 'customer@acme.test')->sole();

    test()->actingAs($customer, 'customer');

    return $customer;
}

test('can register a new customer', function () {
    visit('/account/register')
        ->fill('name', 'New Customer')
        ->fill('email', 'new-customer-e2e@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->press('button:has-text("Create account")')
        ->waitForText('Welcome back')
        ->assertPathIs('/account')
        ->assertSee('New Customer')
        ->assertNoJavascriptErrors();
});

test('shows validation errors for duplicate email registration', function () {
    visit('/account/register')
        ->fill('name', 'Duplicate Customer')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->press('button:has-text("Create account")')
        ->waitForText('already been taken')
        ->assertSee('already been taken')
        ->assertNoJavascriptErrors();
});

test('shows validation errors for mismatched passwords', function () {
    visit('/account/register')
        ->fill('name', 'Test Customer')
        ->fill('email', 'mismatch@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'different456')
        ->press('button:has-text("Create account")')
        ->waitForText('does not match')
        ->assertSee('password')
        ->assertNoJavascriptErrors();
});

test('can log in as existing customer', function () {
    visit('/account/login')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->press('button:has-text("Log in")')
        ->waitForText('Welcome back')
        ->assertPathIs('/account')
        ->assertSee('John Doe')
        ->assertNoJavascriptErrors();
});

test('shows error for invalid customer credentials', function () {
    visit('/account/login')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'wrongpassword')
        ->press('button:has-text("Log in")')
        ->waitForText('Invalid credentials')
        ->assertSee('Invalid credentials')
        ->assertNoJavascriptErrors();
});

test('redirects unauthenticated customers to login', function () {
    visit('/account')
        ->assertPathIs('/account/login')
        ->assertSee('Log in to your account')
        ->assertNoJavascriptErrors();
});

test('shows order history for logged-in customer', function () {
    customerAccountTestLoginAsSeededCustomer();

    visit('/account/orders')
        ->assertSee('Order history')
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004')
        ->assertNoJavascriptErrors();
});

test('shows order detail for customer order', function () {
    customerAccountTestLoginAsSeededCustomer();

    visit('/account/orders')
        ->click('table a:has-text("#1001")')
        ->waitForText('Order #1001')
        ->assertPathIs('/account/orders/1001')
        ->assertSee('Subtotal')
        ->assertSee('Total')
        ->assertNoJavascriptErrors();
});

test('can view addresses', function () {
    customerAccountTestLoginAsSeededCustomer();

    visit('/account/addresses')
        ->assertSee('Your addresses')
        ->assertSee('Hauptstrasse 1')
        ->assertSee('Friedrichstrasse 100')
        ->assertNoJavascriptErrors();
});

test('can add a new address', function () {
    customerAccountTestLoginAsSeededCustomer();

    visit('/account/addresses')
        ->press('Add new address')
        ->waitForText('Add address')
        ->fill('address-first-name', 'John')
        ->fill('address-last-name', 'Doe')
        ->fill('address-address1', 'New Street 42')
        ->fill('address-city', 'Hamburg')
        ->fill('address-postal-code', '20095')
        ->fill('address-country', 'Germany')
        ->fill('address-country-code', 'DE')
        ->press('button:has-text("Save address")')
        ->waitForText('New Street 42')
        ->assertSee('New Street 42')
        ->assertSee('Hamburg')
        ->assertNoJavascriptErrors();

    expect(
        CustomerAddress::query()->where('address_json->address1', 'New Street 42')->exists()
    )->toBeTrue();
});

test('can edit an existing address', function () {
    customerAccountTestLoginAsSeededCustomer();

    visit('/account/addresses')
        ->press('Edit')
        ->waitForText('Edit address')
        ->fill('address-city', 'Frankfurt')
        ->press('button:has-text("Save address")')
        ->waitForText('Frankfurt')
        ->assertSee('Frankfurt')
        ->assertNoJavascriptErrors();

    expect(
        CustomerAddress::query()->where('address_json->city', 'Frankfurt')->exists()
    )->toBeTrue();
});

test('can log out', function () {
    visit('/account/login')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->press('button:has-text("Log in")')
        ->waitForText('Welcome back')
        ->press('Log out')
        ->waitForText('Log in to your account')
        ->assertPathIs('/account/login')
        ->assertNoJavascriptErrors();
});
