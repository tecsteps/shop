<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

/**
 * Select size M + color Black on the Classic Cotton T-Shirt PDP and add it
 * to the cart. Waits for the Livewire round-trip (the cart drawer opens).
 */
function cartTestAddTShirtToCart($page)
{
    return $page->visit('/products/classic-cotton-t-shirt')
        ->click('button:text-is("M")')
        ->click('button[aria-label="Black"]')
        ->click('button:has-text("Add to cart")')
        ->wait(1);
}

/**
 * Add the T-Shirt and land on the full cart page.
 */
function cartTestVisitCartWithTShirt($page)
{
    return cartTestAddTShirtToCart($page)
        ->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt');
}

/**
 * Enter a discount code on the cart page and submit the discount form.
 */
function cartTestApplyDiscount($page, string $code)
{
    return $page->fill('#cart-discount-code', $code)
        ->press('form:has(#cart-discount-code) button[type="submit"]')
        ->wait(1);
}

test('8.1 can add product to cart', function (): void {
    cartTestAddTShirtToCart($this)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});

test('8.2 can view cart with added item', function (): void {
    cartTestVisitCartWithTShirt($this)
        ->assertSee('Your Cart')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});

test('8.3 can update quantity in cart', function (): void {
    cartTestVisitCartWithTShirt($this)
        ->click('button[aria-label="Increase quantity of Classic Cotton T-Shirt"]:visible')
        ->assertSee('49.98')
        ->assertNoJavascriptErrors();
});

test('8.4 can remove item from cart', function (): void {
    cartTestVisitCartWithTShirt($this)
        ->click('button[aria-label="Remove Classic Cotton T-Shirt from cart"]:visible')
        ->assertSee('Your cart is empty')
        ->assertNoJavascriptErrors();
});

test('8.5 can add multiple different products', function (): void {
    cartTestAddTShirtToCart($this)
        ->navigate('/products/premium-slim-fit-jeans')
        ->click('button:has-text("Add to cart")')
        ->wait(1)
        ->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavascriptErrors();
});

test('8.6 can apply valid discount code WELCOME10', function (): void {
    cartTestApplyDiscount(cartTestVisitCartWithTShirt($this), 'WELCOME10')
        ->assertSee('WELCOME10')
        ->assertSee('Discount')
        ->assertSee('2.50')
        ->assertNoJavascriptErrors();
});

test('8.7 shows error for invalid discount code', function (): void {
    // The app's message is "This discount code is invalid." (spec: "Invalid discount code").
    cartTestApplyDiscount(cartTestVisitCartWithTShirt($this), 'INVALID')
        ->assertSee('discount code is invalid')
        ->assertNoJavascriptErrors();
});

test('8.8 shows error for expired discount code', function (): void {
    cartTestApplyDiscount(cartTestVisitCartWithTShirt($this), 'EXPIRED20')
        ->assertSee('expired')
        ->assertNoJavascriptErrors();
});

test('8.9 shows error for maxed out discount code', function (): void {
    cartTestApplyDiscount(cartTestVisitCartWithTShirt($this), 'MAXED')
        ->assertSee('usage limit')
        ->assertNoJavascriptErrors();
});

test('8.10 can apply free shipping discount', function (): void {
    cartTestApplyDiscount(cartTestVisitCartWithTShirt($this), 'FREESHIP')
        ->assertSee('FREESHIP')
        ->assertSee('Free shipping')
        ->assertNoJavascriptErrors();
});

test('8.11 can apply FLAT5 discount for fixed amount off', function (): void {
    cartTestApplyDiscount(cartTestVisitCartWithTShirt($this), 'FLAT5')
        ->assertSee('FLAT5')
        ->assertSee('5.00')
        ->assertNoJavascriptErrors();
});

test('8.12 shows subtotal and total in cart', function (): void {
    cartTestVisitCartWithTShirt($this)
        ->assertSee('Subtotal')
        ->assertSee('Total')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});
