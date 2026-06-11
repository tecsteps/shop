<?php

it('can add product to cart', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});

it('can view cart with added item', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->assertSee('Your Cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});

it('can update quantity in cart', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->click('table [aria-label="Increase quantity"]')
        ->assertSee('49.98')
        ->assertNoJavascriptErrors();
});

it('can remove item from cart', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->click('table [aria-label^="Remove"]')
        ->assertSee('Your cart is empty')
        ->assertNoJavascriptErrors();
});

it('can add multiple different products', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/products/premium-slim-fit-jeans')
        ->assertSee('Premium Slim Fit Jeans')
        ->click('32')
        ->click('label[title="Blue"]')
        ->click('Add to cart')
        ->assertSee('Added to cart');

    $page->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavascriptErrors();
});

it('can apply valid discount code WELCOME10', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->assertSee('24.99')
        ->fill('cart-discount-code', 'WELCOME10')
        ->click('Apply')
        ->assertSee('WELCOME10')
        ->assertSee('Discount')
        ->assertSee('2.50')
        ->assertNoJavascriptErrors();
});

it('shows error for invalid discount code', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->fill('cart-discount-code', 'INVALID')
        ->click('Apply')
        ->assertSee('Invalid discount code')
        ->assertNoJavascriptErrors();
});

it('shows error for expired discount code', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->fill('cart-discount-code', 'EXPIRED20')
        ->click('Apply')
        ->assertSee('expired')
        ->assertNoJavascriptErrors();
});

it('shows error for maxed out discount code', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->fill('cart-discount-code', 'MAXED')
        ->click('Apply')
        ->assertSee('usage limit')
        ->assertNoJavascriptErrors();
});

it('can apply free shipping discount', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->fill('cart-discount-code', 'FREESHIP')
        ->click('Apply')
        ->assertSee('FREESHIP')
        ->assertSee('(Free shipping)')
        ->assertNoJavascriptErrors();
});

it('can apply FLAT5 discount for fixed amount off', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->fill('cart-discount-code', 'FLAT5')
        ->click('Apply')
        ->assertSee('FLAT5')
        ->assertSee('5.00')
        ->assertNoJavascriptErrors();
});

it('shows subtotal and total in cart', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->assertSee('Subtotal')
        ->assertSee('24.99')
        ->assertNoJavascriptErrors();
});
