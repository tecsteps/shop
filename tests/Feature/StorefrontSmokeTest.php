<?php

use Database\Seeders\DemoStoreSeeder;

beforeEach(function () {
    $this->seed(DemoStoreSeeder::class);
    $this->domain = 'shop.test';
});

function visitStorefront(string $path)
{
    return test()->get('http://shop.test'.$path);
}

it('renders the home page with the store name and a product', function () {
    $response = visitStorefront('/');

    $response->assertOk();
    $response->assertSee('Demo Store');
    $response->assertSee('Classic Crew Tee');
});

it('renders the collections index', function () {
    visitStorefront('/collections')
        ->assertOk()
        ->assertSee('Apparel')
        ->assertSee('Accessories');
});

it('renders a product page with price', function () {
    visitStorefront('/products/classic-crew-tee')
        ->assertOk()
        ->assertSee('Classic Crew Tee')
        ->assertSee('24.99');
});

it('renders the cart page', function () {
    visitStorefront('/cart')
        ->assertOk()
        ->assertSee('Your cart');
});

it('renders the search page', function () {
    visitStorefront('/search')
        ->assertOk()
        ->assertSee('Search');
});

it('renders a published CMS page', function () {
    visitStorefront('/pages/about')
        ->assertOk()
        ->assertSee('About us');
});

it('renders the account login', function () {
    visitStorefront('/account/login')
        ->assertOk()
        ->assertSee('Sign in');
});

it('shows the admin login', function () {
    visitStorefront('/admin/login')
        ->assertOk()
        ->assertSee('Sign in to the admin');
});

it('redirects guests from the admin dashboard', function () {
    test()->get('http://shop.test/admin')
        ->assertRedirect('/admin/login');
});
