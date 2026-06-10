<?php

it('shows the customer list', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Customers")')
        ->assertSeeIn('h1[data-flux-heading]', 'Customers')
        ->assertSee('customer@acme.test')
        ->assertSee('John Doe')
        ->assertNoJavascriptErrors();
});

it('shows customer detail with order history', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Customers")')
        ->assertSee('John Doe')
        ->click('John Doe')
        ->assertSeeIn('h1[data-flux-heading]', 'John Doe')
        ->assertSee('customer@acme.test')
        ->assertSee('#1001')
        ->assertNoJavascriptErrors();
});

it('shows customer addresses', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Customers")')
        ->click('John Doe')
        ->assertSee('Addresses')
        ->assertNoJavascriptErrors();
});
