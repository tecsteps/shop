<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

function loginBrowserCustomer(): mixed
{
    return visit('/account/login')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'password')
        ->click('form button[type="submit"]')
        ->waitForText('Welcome, John Doe');
}

it('rejects invalid customer credentials', function (): void {
    visit('/account/login')
        ->fill('email', 'customer@acme.test')
        ->fill('password', 'incorrect')
        ->click('form button[type="submit"]')
        ->waitForText('Invalid credentials')
        ->assertPathIs('/account/login')
        ->assertNoJavaScriptErrors();
});

it('signs in and displays the customer dashboard', function (): void {
    loginBrowserCustomer()
        ->assertPathIs('/account')
        ->assertSeeLink('Orders')
        ->assertSeeLink('Addresses')
        ->assertNoJavaScriptErrors();
});

it('shows only the signed-in customer order history', function (): void {
    loginBrowserCustomer()
        ->click('Orders')
        ->waitForText('Your orders')
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004')
        ->assertDontSee('#1003')
        ->assertNoJavaScriptErrors();
});

it('creates a saved address and signs out', function (): void {
    loginBrowserCustomer()
        ->click('Addresses')
        ->fill('label', 'Office')
        ->fill('[name="address.first_name"]', 'John')
        ->fill('[name="address.last_name"]', 'Doe')
        ->fill('[name="address.address1"]', 'Teststrasse 42')
        ->fill('[name="address.city"]', 'Berlin')
        ->fill('[name="address.zip"]', '10115')
        ->fill('[name="address.country_code"]', 'DE')
        ->click('form button[type="submit"]')
        ->waitForText('Office')
        ->navigate('/account')
        ->press('Sign out')
        ->waitForText('Sign in')
        ->assertPathIs('/account/login')
        ->assertNoJavaScriptErrors();
});
