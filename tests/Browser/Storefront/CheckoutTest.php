<?php

use App\Models\Order;

it('completes full checkout with credit card', function (): void {
    $page = browserReachCheckoutPaymentStep('test-buyer@example.com');

    $page->assertRadioSelected('payment-method', 'credit_card')
        ->fill('card-number', '4242 4242 4242 4242')
        ->fill('card-name', 'Test Buyer')
        ->fill('card-expiry', '12/28')
        ->fill('card-cvc', '123')
        ->assertSee('29.98')
        ->click('button:has-text("Pay now")')
        ->assertSee('Thank you');

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->order_number)->toStartWith('#');

    $page->assertSee($order->order_number)
        ->assertNoJavascriptErrors();
});

it('shows shipping methods based on German address', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->fill('checkout-email', 'test@example.com')
        ->click('Continue');

    browserFillCheckoutAddress($page, 'Hans', 'Mueller', 'Berliner Str. 10', 'Munich', '80331', 'DE');

    $page->assertSee('Standard Shipping')
        ->assertSee('4.99')
        ->assertNoJavascriptErrors();
});

it('shows international shipping methods for non-DE address', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->fill('checkout-email', 'test@example.com')
        ->click('Continue');

    browserFillCheckoutAddress($page, 'John', 'Smith', '123 Main St', 'New York', '10001', 'US');

    $page->assertSee('International')
        ->assertDontSee('Standard Shipping')
        ->assertNoJavascriptErrors();
});

it('applies discount during checkout', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->fill('cart-discount-code', 'FLAT5')
        ->click('Apply')
        ->assertSee('FLAT5')
        ->click('[aria-label="Close cart"]')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->fill('checkout-email', 'test@example.com')
        ->click('Continue');

    browserFillCheckoutAddress($page, 'Test', 'User', 'Teststr 1', 'Berlin', '10115', 'DE');

    $page->assertSee('Standard Shipping')
        ->click('Standard Shipping')
        ->click('Continue')
        ->assertSee('Select a payment method')
        ->assertSee('FLAT5')
        ->assertSee('5.00')
        ->assertSee('24.98')
        ->assertNoJavascriptErrors();
});

it('validates required contact email', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->click('Continue')
        ->assertSee('The email field is required')
        ->assertNoJavascriptErrors();
});

it('validates required shipping address fields', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->fill('checkout-email', 'test@example.com')
        ->click('Continue')
        ->assertSee('First name')
        ->click('Continue')
        ->assertSee('The first name field is required')
        ->assertSee('The last name field is required')
        ->assertSee('The address field is required')
        ->assertSee('The city field is required')
        ->assertSee('The postal code field is required')
        ->assertSee('The country field is required')
        ->assertNoJavascriptErrors();
});

it('validates invalid postal code format', function (): void {
    $page = browserAddClassicTeeToCart();

    $page->navigate('/cart')
        ->click('Checkout')
        ->assertSee('Contact information')
        ->fill('checkout-email', 'test@example.com')
        ->click('Continue');

    browserFillCheckoutAddress($page, 'Test', 'User', 'Teststr 1', 'Berlin', 'INVALID', 'DE');

    $page->assertSee('The postal code format is invalid')
        ->assertNoJavascriptErrors();
});

it('prevents checkout with empty cart', function (): void {
    $page = visit('/cart');

    $page->assertSee('Your cart is empty')
        ->assertDontSee('Checkout')
        ->assertNoJavascriptErrors();
});

it('completes checkout with PayPal', function (): void {
    $page = browserReachCheckoutPaymentStep();

    $page->click('PayPal')
        ->click('button:has-text("Pay with PayPal")')
        ->assertSee('Thank you')
        ->assertSee('Payment method')
        ->assertSee('PayPal')
        ->assertNoJavascriptErrors();
});

it('completes checkout with bank transfer', function (): void {
    $page = browserReachCheckoutPaymentStep();

    $page->click('Bank Transfer')
        ->click('button:has-text("Place order")')
        ->assertSee('Thank you')
        ->assertSee('Bank Transfer Instructions')
        ->assertSee('IBAN')
        ->assertSee('DE89 3704 0044 0532 0130 00')
        ->assertSee('BIC')
        ->assertSee('COBADEFFXXX')
        ->assertSee('Reference');

    $order = Order::query()->latest('id')->firstOrFail();

    $page->assertSee($order->order_number)
        ->assertNoJavascriptErrors();
});

it('shows error for declined credit card', function (): void {
    $page = browserReachCheckoutPaymentStep();

    $page->assertRadioSelected('payment-method', 'credit_card')
        ->fill('card-number', '4000 0000 0000 0002')
        ->fill('card-name', 'Test Buyer')
        ->fill('card-expiry', '12/28')
        ->fill('card-cvc', '123')
        ->click('button:has-text("Pay now")')
        ->assertSee('declined')
        ->assertPathIs('/checkout')
        ->assertNoJavascriptErrors();
});

it('shows error for insufficient funds', function (): void {
    $page = browserReachCheckoutPaymentStep();

    $page->fill('card-number', '4000 0000 0000 9995')
        ->fill('card-name', 'Test Buyer')
        ->fill('card-expiry', '12/28')
        ->fill('card-cvc', '123')
        ->click('button:has-text("Pay now")')
        ->assertSee('insufficient')
        ->assertPathIs('/checkout')
        ->assertNoJavascriptErrors();
});

it('switches between payment method forms', function (): void {
    $page = browserReachCheckoutPaymentStep();

    $page->assertRadioSelected('payment-method', 'credit_card')
        ->assertVisible('#card-number')
        ->assertVisible('#card-name')
        ->click('PayPal')
        ->assertNotPresent('#card-number')
        ->assertVisible('button:has-text("Pay with PayPal")')
        ->click('Bank Transfer')
        ->assertVisible('button:has-text("Place order")')
        ->assertSee('you will receive bank transfer instructions')
        ->assertNoJavascriptErrors();
});
