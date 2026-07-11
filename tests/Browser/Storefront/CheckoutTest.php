<?php

beforeEach(function (): void {
    seedBrowserShop($this);
});

function startBrowserCheckout(): mixed
{
    return visit('/products/classic-cotton-t-shirt')
        ->press('Add to cart')
        ->waitForText('Shopping cart')
        ->navigate('/cart')
        ->waitForText('Classic Cotton T-Shirt')
        ->press('Checkout')
        ->waitForText('1. Contact and shipping address');
}

function fillBrowserShippingAddress(mixed $page): mixed
{
    return $page
        ->fill('email', 'buyer@example.com')
        ->fill('[name="shipping.first_name"]', 'Taylor')
        ->fill('[name="shipping.last_name"]', 'Buyer')
        ->fill('[name="shipping.address1"]', 'Alexanderplatz 1')
        ->fill('[name="shipping.city"]', 'Berlin')
        ->fill('[name="shipping.postal_code"]', '10178')
        ->fill('[name="shipping.country"]', 'DE')
        ->press('Continue to shipping')
        ->waitForText('Standard Shipping');
}

function chooseBrowserShippingAndPayment(mixed $page, string $method): mixed
{
    return $page
        ->click('Standard Shipping')
        ->press('Continue to payment')
        ->waitForText('3. Payment')
        ->click($method)
        ->press('Review payment')
        ->waitForText('Pay now');
}

it('completes a credit-card checkout and shows confirmation', function (): void {
    $page = fillBrowserShippingAddress(startBrowserCheckout());

    chooseBrowserShippingAndPayment($page, 'Credit card')
        ->fill('cardNumber', '4242 4242 4242 4242')
        ->press('Pay now')
        ->waitForText('Thank you for your order!')
        ->assertPathContains('/confirmation')
        ->assertSee('Order #')
        ->assertNoJavaScriptErrors();
});

it('applies a discount and completes a bank-transfer checkout', function (): void {
    $page = fillBrowserShippingAddress(startBrowserCheckout());

    $page->fill('discountCode', 'WELCOME10')
        ->press('Apply discount')
        ->wait(0.3);

    chooseBrowserShippingAndPayment($page, 'Bank transfer')
        ->press('Pay now')
        ->waitForText('Thank you for your order!')
        ->assertSee('Bank transfer')
        ->assertPathContains('/confirmation')
        ->assertNoJavaScriptErrors();
});
