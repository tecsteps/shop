<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('loads the storefront home page', function (): void {
    $page = visit('/');

    $page->assertSee('Shop')
        ->assertNoJavaScriptErrors();
});

it('loads a collection page', function (): void {
    $page = visit('/collections/featured');

    $page->assertSee('Featured')
        ->assertNoJavaScriptErrors();
});

it('loads a product detail page', function (): void {
    $page = visit('/products/classic-tee');

    $page->assertSee('Classic Tee')
        ->assertSee('Add to Cart')
        ->assertNoJavaScriptErrors();
});

it('loads the cart page', function (): void {
    $page = visit('/cart');

    $page->assertSee('Your cart')
        ->assertNoJavaScriptErrors();
});

it('loads the admin login page', function (): void {
    $page = visit('/admin/login');

    $page->assertSee('Admin sign in')
        ->assertNoJavaScriptErrors();
});
