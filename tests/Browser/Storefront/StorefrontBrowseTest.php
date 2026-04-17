<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('renders the home page with the store name', function (): void {
    $page = visit('/');

    $page->assertSee('Welcome to Shop')
        ->assertSee('Shop the collection')
        ->assertNoJavaScriptErrors();
});

it('shows products when visiting a collection', function (): void {
    $page = visit('/collections/featured');

    $page->assertSee('Featured')
        ->assertSee('Classic Tee')
        ->assertNoJavaScriptErrors();
});
