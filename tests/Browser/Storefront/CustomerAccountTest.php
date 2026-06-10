<?php

it('can register a new customer', function (): void {
    $page = visit('/account/register');

    $page->assertSee('Create an account')
        ->fill('name', 'New Customer')
        ->fill('email', 'new-customer-e2e@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->click('@customer-register-button')
        ->assertSee('My Account')
        ->assertNoJavascriptErrors();
});

it('shows validation errors for duplicate email registration', function (): void {
    $page = visit('/account/register');

    $page->fill('name', 'Duplicate Customer')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->click('@customer-register-button')
        ->assertSee('already been taken')
        ->assertNoJavascriptErrors();
});

it('shows validation errors for mismatched passwords', function (): void {
    $page = visit('/account/register');

    $page->fill('name', 'Test Customer')
        ->fill('email', 'mismatch@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'different456')
        ->click('@customer-register-button')
        ->assertSee('password')
        ->assertSee('does not match')
        ->assertNoJavascriptErrors();
});

it('can log in as existing customer', function (): void {
    $page = browserLoginAsCustomer();

    $page->assertSee('My Account')
        ->assertSee('John Doe')
        ->assertNoJavascriptErrors();
});

it('shows error for invalid customer credentials', function (): void {
    $page = visit('/account/login');

    $page->fill('email', 'customer@acme.test')
        ->fill('password', 'wrongpassword')
        ->click('@customer-login-button')
        ->assertSee('Invalid credentials')
        ->assertNoJavascriptErrors();
});

it('redirects unauthenticated customers to login', function (): void {
    $page = visit('/account');

    $page->assertSee('Log in')
        ->assertNoJavascriptErrors();
});

it('shows order history for logged-in customer', function (): void {
    $page = browserLoginAsCustomer();

    $page->click('Orders')
        ->assertSee('Order History')
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004')
        ->assertNoJavascriptErrors();
});

it('shows order detail for customer order', function (): void {
    $page = browserLoginAsCustomer();

    $page->click('Orders')
        ->assertSee('Order History')
        ->click('a:visible:has-text("#1001")')
        ->assertSee('#1001')
        ->assertSee('Subtotal')
        ->assertSee('Total')
        ->assertNoJavascriptErrors();
});

it('can view addresses', function (): void {
    $page = browserLoginAsCustomer();

    $page->click('Addresses')
        ->assertSee('Your Addresses')
        ->assertSee('Hauptstrasse 1')
        ->assertSee('Friedrichstrasse 100')
        ->assertNoJavascriptErrors();
});

it('can add a new address', function (): void {
    $page = browserLoginAsCustomer();

    $page->click('Addresses')
        ->assertSee('Your Addresses')
        ->click('@add-address-button')
        ->assertSee('Add new address')
        ->fill('[id="form.first_name"]', 'John')
        ->fill('[id="form.last_name"]', 'Doe')
        ->fill('[id="form.address1"]', 'New Street 42')
        ->fill('[id="form.city"]', 'Hamburg')
        ->fill('[id="form.postal_code"]', '20095')
        ->select('[id="form.country_code"]', 'DE')
        ->click('@save-address-button')
        ->assertSee('Address saved')
        ->assertSee('New Street 42')
        ->assertSee('Hamburg')
        ->assertNoJavascriptErrors();
});

it('can edit an existing address', function (): void {
    $page = browserLoginAsCustomer();

    $page->click('Addresses')
        ->assertSee('Your Addresses')
        ->click('ul[role="list"] li:first-child button:has-text("Edit")')
        ->assertSee('Edit address')
        ->fill('[id="form.city"]', 'Frankfurt')
        ->click('@save-address-button')
        ->assertSee('Address saved')
        ->assertSee('Frankfurt')
        ->assertNoJavascriptErrors();
});

it('can log out', function (): void {
    $page = browserLoginAsCustomer();

    $page->assertSee('My Account')
        ->click('@customer-logout-button')
        ->assertSee('Log in')
        ->assertNoJavascriptErrors();
});
