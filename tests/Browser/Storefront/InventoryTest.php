<?php

it('blocks add-to-cart for out-of-stock deny-policy product', function (): void {
    $page = visit('/products/limited-edition-sneakers');

    $page->assertSee('Sold out')
        ->assertDontSee('Add to cart')
        ->assertButtonDisabled('button:has-text("Sold out")')
        ->assertNoJavascriptErrors();
});

it('allows add-to-cart for out-of-stock continue-policy product', function (): void {
    $page = visit('/products/backorder-denim-jacket');

    $page->assertSee('Available on backorder')
        ->assertButtonEnabled('Add to cart')
        ->click('Add to cart')
        ->assertSee('Added to cart');

    $page->navigate('/cart')
        ->assertSee('Backorder Denim Jacket')
        ->assertNoJavascriptErrors();
});

it('shows correct stock status for in-stock product', function (): void {
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertButtonEnabled('Add to cart')
        ->assertDontSee('Sold out')
        ->assertDontSee('Available on backorder')
        ->assertNoJavascriptErrors();
});

it('prevents adding more than available stock for deny-policy product', function (): void {
    // The seeded M / Black variant has exactly 15 units on hand (deny policy).
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertSee('Classic Cotton T-Shirt')
        ->click('M')
        ->click('label[title="Black"]')
        ->click('input[aria-label="Quantity"]')
        ->keys('input[aria-label="Quantity"]', ['Backspace'])
        ->type('input[aria-label="Quantity"]', '15')
        ->assertScript("Alpine.\$data(document.querySelector('input[aria-label=\"Quantity\"]')).quantity", 15)
        ->click('Add to cart')
        ->assertSee('Added to cart');

    $page->navigate('/cart')
        ->assertSee('Classic Cotton T-Shirt')
        ->click('table [aria-label="Increase quantity"]')
        ->assertSee('Not enough stock available')
        ->assertNoJavascriptErrors();
});
