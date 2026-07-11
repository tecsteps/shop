<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

function addClassicShirtToBrowserCart(): mixed
{
    return visit('/products/classic-cotton-t-shirt')
        ->press('Add to cart')
        ->waitForText('Shopping cart')
        ->navigate('/cart')
        ->waitForText('Classic Cotton T-Shirt');
}

it('starts with an empty cart', function (): void {
    visit('/cart')
        ->assertSee('Your cart is empty')
        ->assertSeeLink('Continue shopping')
        ->assertNoJavaScriptErrors();
});

it('adds a product and displays the correct line total', function (): void {
    addClassicShirtToBrowserCart()
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('Subtotal')
        ->assertSee('Checkout')
        ->assertNoJavaScriptErrors();
});

it('updates quantity and recalculates totals', function (): void {
    addClassicShirtToBrowserCart()
        ->click('[aria-label="Increase quantity"]')
        ->waitForText('49.98')
        ->assertSee('49.98')
        ->click('dialog[open] [aria-label="Close modal"]')
        ->click('[aria-label="Decrease quantity"]')
        ->waitForText('24.99')
        ->assertNoJavaScriptErrors();
});

it('removes a line from the cart', function (): void {
    addClassicShirtToBrowserCart()
        ->press('Remove')
        ->waitForText('Your cart is empty')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});
