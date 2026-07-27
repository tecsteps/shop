<?php

use App\Models\CartLine;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

test('blocks add-to-cart for out-of-stock deny-policy product', function () {
    visit('/products/limited-edition-sneakers')
        ->assertSee('Out of stock')
        ->assertSee('Sold out')
        ->assertDisabled('button[wire\:click="addToCart"]')
        ->assertNoJavaScriptErrors();
});

test('allows add-to-cart for out-of-stock continue-policy product', function () {
    $page = visit('/products/backorder-denim-jacket');

    $page->assertSee('Available on backorder')
        ->assertEnabled('button[wire\:click="addToCart"]')
        ->press('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->assertSee('Your Cart')
        ->assertSee('Backorder Denim Jacket')
        ->assertNoJavaScriptErrors();

    expect(CartLine::query()->count())->toBe(1);
});

test('shows correct stock status for in-stock product', function () {
    visit('/products/classic-cotton-t-shirt')
        ->assertSee('In stock')
        ->assertSee('Add to cart')
        ->assertEnabled('button[wire\:click="addToCart"]')
        ->assertDontSee('Sold out')
        ->assertDontSee('Available on backorder')
        ->assertNoJavaScriptErrors();
});

test('prevents adding more than available stock for deny-policy product', function () {
    // The M / Black variant of the Classic Cotton T-Shirt has 15 in stock.
    $page = visit('/products/classic-cotton-t-shirt');

    $page->press('M')
        ->wait(1)
        ->press('button[aria-label="Black"]')
        ->wait(1)
        ->fill('quantity-quantity', '14')
        ->wait(1)
        ->press('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertScript("document.querySelector('table span.tabular-nums')?.textContent.trim()", '14');

    // 14 + 1 reaches the stock limit of 15. The increase button is scoped
    // to the cart page table because the cart drawer uses the same label.
    $page->press('table button[aria-label="Increase quantity of Classic Cotton T-Shirt"]')
        ->wait(1)
        ->assertScript("document.querySelector('table span.tabular-nums')?.textContent.trim()", '15');

    // Any further increment is rejected and the quantity stays capped.
    $page->press('table button[aria-label="Increase quantity of Classic Cotton T-Shirt"]')
        ->wait(1)
        ->assertScript("document.querySelector('table span.tabular-nums')?.textContent.trim()", '15')
        ->assertNoJavaScriptErrors();

    expect(CartLine::query()->sole()->quantity)->toBe(15);
});
