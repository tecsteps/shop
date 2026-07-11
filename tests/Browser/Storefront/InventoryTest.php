<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

it('prevents adding a sold-out deny-policy variant', function (): void {
    visit('/products/limited-edition-sneakers')
        ->assertSee('Out of stock')
        ->assertDisabled('button[wire\\:click="addToCart"]')
        ->assertNoJavaScriptErrors();
});

it('allows adding a continue-policy backorder variant', function (): void {
    visit('/products/backorder-denim-jacket')
        ->assertSee('Available on backorder')
        ->assertEnabled('button[wire\\:click="addToCart"]')
        ->press('Add to cart')
        ->waitForText('Shopping cart')
        ->navigate('/cart')
        ->assertSee('Backorder Denim Jacket')
        ->assertNoJavaScriptErrors();
});
