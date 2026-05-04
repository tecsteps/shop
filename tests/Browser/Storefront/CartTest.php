<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function storefrontCartHost(): array
{
    return ['host' => 'shop.test'];
}

function storefrontCartAddClassicTShirt(): mixed
{
    return visit('/products/classic-cotton-t-shirt', storefrontCartHost())
        ->click('M')
        ->wait(1)
        ->click('Black')
        ->wait(1)
        ->click('Add to cart')
        ->wait(1);
}

function storefrontCartPageWithClassicTShirt(): mixed
{
    return storefrontCartAddClassicTShirt()
        ->navigate('/cart')
        ->wait(1)
        ->assertSee('Your Cart')
        ->assertSee('Classic Cotton T-Shirt');
}

function storefrontCartApplyDiscount(string $code): mixed
{
    return storefrontCartPageWithClassicTShirt()
        ->fill('input[wire\\:model="discountCode"]', $code)
        ->click('button:has-text("Apply")')
        ->wait(1);
}

test('can add product to cart', function (): void {
    storefrontCartAddClassicTShirt()
        ->navigate('/cart')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

test('can view cart with added item', function (): void {
    storefrontCartPageWithClassicTShirt()
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});

test('can update quantity in cart', function (): void {
    storefrontCartPageWithClassicTShirt()
        ->click('main button[aria-label="Increase Classic Cotton T-Shirt quantity"]')
        ->wait(1)
        ->assertSee('2 items')
        ->assertSee('49.98')
        ->assertNoJavaScriptErrors();
});

test('can remove item from cart', function (): void {
    storefrontCartPageWithClassicTShirt()
        ->click('main button[aria-label="Remove Classic Cotton T-Shirt"]')
        ->wait(1)
        ->assertSee('Your cart is empty')
        ->assertNoJavaScriptErrors();
});

test('can add multiple different products', function (): void {
    storefrontCartAddClassicTShirt()
        ->navigate('/products/premium-slim-fit-jeans')
        ->wait(1)
        ->click('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavaScriptErrors();
});

test('can apply valid discount code welcome ten', function (): void {
    storefrontCartApplyDiscount('WELCOME10')
        ->assertSee('WELCOME10')
        ->assertSee('Discount')
        ->assertSee('2.50')
        ->assertNoJavaScriptErrors();
});

test('shows error for invalid discount code', function (): void {
    storefrontCartApplyDiscount('INVALID')
        ->assertSee('Invalid discount code')
        ->assertNoJavaScriptErrors();
});

test('shows error for expired discount code', function (): void {
    storefrontCartApplyDiscount('EXPIRED20')
        ->assertSee('expired')
        ->assertNoJavaScriptErrors();
});

test('shows error for maxed out discount code', function (): void {
    storefrontCartApplyDiscount('MAXED')
        ->assertSee('usage limit')
        ->assertNoJavaScriptErrors();
});

test('can apply free shipping discount', function (): void {
    storefrontCartApplyDiscount('FREESHIP')
        ->assertSee('FREESHIP')
        ->assertSee('Free shipping')
        ->assertNoJavaScriptErrors();
});

test('can apply flat five discount for fixed amount off', function (): void {
    storefrontCartApplyDiscount('FLAT5')
        ->assertSee('FLAT5')
        ->assertSee('5.00')
        ->assertNoJavaScriptErrors();
});

test('shows subtotal and total in cart', function (): void {
    storefrontCartPageWithClassicTShirt()
        ->assertSee('Subtotal')
        ->assertSee('Estimated total')
        ->assertSee('24.99')
        ->assertNoJavaScriptErrors();
});
