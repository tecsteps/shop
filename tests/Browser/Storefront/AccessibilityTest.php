<?php

it('home page has no JavaScript errors or console warnings', function (): void {
    $page = visit('/');

    $page->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('home page has proper heading hierarchy', function (): void {
    $page = visit('/');

    $page->assertScript("document.querySelectorAll('h1').length", 1)
        ->assertSee('Acme Fashion')
        ->assertNoJavascriptErrors();
});

it('product page has proper ARIA labels for variant selector', function (): void {
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertSee('Size')
        ->assertSee('Color')
        ->assertVisible('button:has-text("Add to cart")')
        ->assertNoJavascriptErrors();
});

it('product page images have alt text', function (): void {
    $page = visit('/products/classic-cotton-t-shirt');

    $page->assertScript(
        "Array.from(document.querySelectorAll('img')).every((img) =>"
        ." img.closest('[aria-hidden=\"true\"]') !== null"
        ." || img.closest('button[aria-label]') !== null"
        ." || img.alt.trim() !== '')"
    )
        ->assertNoJavascriptErrors();
});

it('customer login form has accessible labels', function (): void {
    $page = visit('/account/login');

    $page->assertSee('Email')
        ->assertSee('Password')
        ->assertScript(
            "Array.from(document.querySelectorAll('form input')).every((input) =>"
            ." input.type === 'hidden'"
            ." || input.getAttribute('aria-label') !== null"
            ." || input.getAttribute('aria-labelledby') !== null"
            .' || (input.labels !== null && input.labels.length > 0))'
        )
        ->assertNoJavascriptErrors();
});

it('admin login form has accessible labels', function (): void {
    $page = visit('/admin/login');

    $page->assertSee('Email')
        ->assertSee('Password')
        ->assertScript(
            "Array.from(document.querySelectorAll('form input')).every((input) =>"
            ." input.type === 'hidden'"
            ." || input.getAttribute('aria-label') !== null"
            ." || input.getAttribute('aria-labelledby') !== null"
            .' || (input.labels !== null && input.labels.length > 0))'
        )
        ->assertNoJavascriptErrors();
});

it('checkout form has accessible labels', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->assertSee('Email')
        ->assertScript(
            "Array.from(document.querySelectorAll('form input')).every((input) =>"
            ." input.type === 'hidden'"
            ." || input.getAttribute('aria-label') !== null"
            ." || input.getAttribute('aria-labelledby') !== null"
            .' || (input.labels !== null && input.labels.length > 0))'
        )
        ->assertNoJavascriptErrors();
});

it('checkout validation errors are accessible', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->click('Continue')
        ->assertSee('The email field is required')
        ->assertAttribute('[id="checkout-email"]', 'aria-describedby', 'checkout-email-error')
        ->assertNoJavascriptErrors();
});

it('can navigate storefront with keyboard only', function (): void {
    $page = visit('/');

    $page->keys('body:first-of-type', ['Tab'])
        ->assertScript("['A', 'BUTTON', 'INPUT'].includes(document.activeElement.tagName)")
        ->keys('nav a:visible:has-text("T-Shirts")', ['Enter'])
        ->assertPathIs('/collections/t-shirts')
        ->assertSee('T-Shirts')
        ->assertNoJavascriptErrors();
});

it('cart page has no console errors or warnings', function (): void {
    $page = visit('/cart');

    $page->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('search page has proper form labels', function (): void {
    $page = visit('/search?q=shirt');

    $page->assertScript(
        "(() => { const input = document.querySelector('input[type=\"search\"]');"
        ." return input !== null && (input.getAttribute('aria-label') !== null || (input.labels !== null && input.labels.length > 0)); })()"
    )
        ->assertNoJavascriptErrors();
});
