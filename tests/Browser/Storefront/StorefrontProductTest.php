<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('adds a variant to cart from product detail page', function (): void {
    $page = visit('/products/classic-tee');

    $page->assertSee('Classic Tee')
        ->assertSee('Add to Cart')
        ->click('Add to Cart')
        ->wait(1)
        ->navigate('/cart')
        ->assertSee('Classic Tee')
        ->assertSee('Subtotal')
        ->assertNoJavaScriptErrors();
});
