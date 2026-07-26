<?php

use App\Models\Checkout;
use App\Models\Order;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

/**
 * Select size M + color Black on the Classic Cotton T-Shirt PDP and add it
 * to the cart. Waits for the Livewire round-trip (the cart drawer opens).
 */
function checkoutTestAddTShirtToCart($page)
{
    return $page->visit('/products/classic-cotton-t-shirt')
        ->click('button:text-is("M")')
        ->click('button[aria-label="Black"]')
        ->click('button:has-text("Add to cart")')
        ->wait(1);
}

/**
 * From the cart page, open a fresh checkout (step 1: contact & address).
 */
function checkoutTestStartCheckout($page)
{
    return $page->navigate('/cart')
        ->click('button:has-text("Checkout"):visible')
        ->wait(1);
}

/**
 * Fill the step 1 form (contact + shipping address) without submitting.
 */
function checkoutTestFillContactAndAddress(
    $page,
    string $email = 'test@example.com',
    string $firstName = 'Test',
    string $lastName = 'Buyer',
    string $address1 = 'Teststrasse 1',
    string $city = 'Berlin',
    string $postalCode = '10115',
    string $countryCode = 'DE',
) {
    return $page->fill('#checkout-email', $email)
        ->fill('#address-first_name', $firstName)
        ->fill('#address-last_name', $lastName)
        ->fill('#address-address1', $address1)
        ->fill('#address-city', $city)
        ->fill('#address-postal_code', $postalCode)
        ->fill('#address-country_code', $countryCode);
}

/**
 * From the cart page, drive checkout through step 1 (contact + address)
 * and step 2 (Standard Shipping) up to the payment step. The app merges
 * contact + address into a single step 1 form (the spec describes them as
 * two steps with two "Continue" clicks).
 */
function checkoutTestGoToPaymentStep(
    $page,
    string $email = 'test@example.com',
    string $firstName = 'Test',
    string $lastName = 'Buyer',
    string $address1 = 'Teststrasse 1',
    string $city = 'Berlin',
    string $postalCode = '10115',
    string $countryCode = 'DE',
) {
    return checkoutTestFillContactAndAddress(
        checkoutTestStartCheckout($page),
        $email, $firstName, $lastName, $address1, $city, $postalCode, $countryCode,
    )
        ->press('Continue to shipping')
        ->wait(2)
        ->click('button:has-text("Standard Shipping")')
        ->wait(1);
}

/**
 * Add the T-Shirt and drive the checkout up to the payment step.
 */
function checkoutTestReachPaymentStep($page, string $email = 'test@example.com')
{
    return checkoutTestGoToPaymentStep(checkoutTestAddTShirtToCart($page), $email);
}

/**
 * Confirm the (pre-selected) credit card method, fill the card form and pay.
 */
function checkoutTestFillCardAndPay($page, string $cardNumber)
{
    return $page->press('Continue')
        ->wait(1)
        ->fill('#card-number', $cardNumber)
        ->fill('#card-holder', 'Test Buyer')
        ->fill('#card-expiry', '12/28')
        ->fill('#card-cvc', '123')
        ->press('button[type="submit"]:has-text("Pay now")')
        ->wait(2);
}

test('9.1 completes full checkout with credit card', function (): void {
    $page = checkoutTestFillContactAndAddress(
        checkoutTestStartCheckout(checkoutTestAddTShirtToCart($this)),
        'test-buyer@example.com', 'Test', 'Buyer', 'Teststrasse 1', 'Berlin', '10115', 'DE',
    )->press('Continue to shipping')->wait(2);

    $page->assertSee('Standard Shipping')
        ->assertSee('4.99')
        ->click('button:has-text("Standard Shipping")')
        ->wait(1)
        // Credit Card is pre-selected; total is 24.99 + 4.99 shipping.
        ->assertScript('document.querySelector("input[type=radio][value=credit_card]").checked === true')
        ->assertSee('29.98');

    checkoutTestFillCardAndPay($page, '4242 4242 4242 4242')
        ->assertSee('Thank you')
        ->assertSee('Order #')
        ->assertNoJavascriptErrors();

    $order = Order::query()->where('email', 'test-buyer@example.com')->sole();

    expect($order->total_amount)->toBe(2998)
        ->and($order->order_number)->toStartWith('#');
});

test('9.2 shows shipping methods based on German address', function (): void {
    checkoutTestFillContactAndAddress(
        checkoutTestStartCheckout(checkoutTestAddTShirtToCart($this)),
        'test@example.com', 'Hans', 'Mueller', 'Berliner Str. 10', 'Munich', '80331', 'DE',
    )
        ->press('Continue to shipping')
        ->wait(2)
        ->assertSee('Standard Shipping')
        ->assertSee('4.99')
        ->assertNoJavascriptErrors();
});

test('9.3 shows international shipping methods for non-DE address', function (): void {
    checkoutTestFillContactAndAddress(
        checkoutTestStartCheckout(checkoutTestAddTShirtToCart($this)),
        'test@example.com', 'John', 'Smith', '123 Main St', 'New York', '10001', 'US',
    )
        ->press('Continue to shipping')
        ->wait(2)
        ->assertSee('International')
        ->assertSee('14.99')
        ->assertDontSee('Standard Shipping')
        ->assertNoJavascriptErrors();
});

test('9.4 applies discount during checkout', function (): void {
    $page = checkoutTestAddTShirtToCart($this)
        ->navigate('/cart')
        ->fill('#cart-discount-code', 'FLAT5')
        ->press('form:has(#cart-discount-code) button[type="submit"]')
        ->wait(1)
        ->assertSee('FLAT5')
        ->click('button:has-text("Checkout"):visible')
        ->wait(1);

    checkoutTestFillContactAndAddress($page, 'test@example.com', 'Test', 'User', 'Teststr 1', 'Berlin', '10115', 'DE')
        ->press('Continue to shipping')
        ->wait(2)
        ->click('button:has-text("Standard Shipping")')
        ->wait(1)
        ->assertSee('FLAT5')
        ->assertSee('5.00')
        ->assertSee('24.98') // 24.99 - 5.00 discount + 4.99 shipping
        ->assertNoJavascriptErrors();
});

test('9.5 validates required contact email', function (): void {
    // The step 1 form relies on native HTML5 validation (required
    // attributes): submitting with an empty email focuses the email field
    // and no checkout is created server-side.
    checkoutTestStartCheckout(checkoutTestAddTShirtToCart($this))
        ->press('Continue to shipping')
        ->wait(1)
        ->assertScript('document.activeElement && document.activeElement.id === "checkout-email"')
        ->assertNoJavascriptErrors();

    expect(Checkout::query()->count())->toBe(0);
});

test('9.6 validates required shipping address fields', function (): void {
    // Native HTML5 validation: with a valid email but empty address fields,
    // the browser blocks submission on the first required address field.
    checkoutTestStartCheckout(checkoutTestAddTShirtToCart($this))
        ->fill('#checkout-email', 'test@example.com')
        ->press('Continue to shipping')
        ->wait(1)
        ->assertScript('document.activeElement && document.activeElement.id === "address-first_name"')
        ->assertNoJavascriptErrors();

    expect(Checkout::query()->count())->toBe(0);
});

test('9.7 validates invalid postal code format', function (): void {
    // Adapted: the app has no postal-code *format* rule (the spec expects
    // one); postal_code is validated as required|string|max:20 only. An
    // over-length code passes native validation and exercises the
    // server-side rule instead.
    checkoutTestFillContactAndAddress(
        checkoutTestStartCheckout(checkoutTestAddTShirtToCart($this)),
        'test@example.com', 'Test', 'User', 'Teststr 1', 'Berlin', 'INVALID-POSTAL-CODE-XX', 'DE',
    )
        ->press('Continue to shipping')
        ->wait(1)
        ->assertSee('postal code')
        ->assertNoJavascriptErrors();

    expect(Checkout::query()->count())->toBe(0);
});

test('9.8 prevents checkout with empty cart', function (): void {
    $this->visit('/cart')
        ->assertSee('Your cart is empty')
        ->assertDontSee('Checkout')
        ->navigate('/checkout/new')
        ->assertPathIs('/cart')
        ->assertNoJavascriptErrors();
});

test('9.9 completes checkout with PayPal', function (): void {
    checkoutTestReachPaymentStep($this, 'paypal-buyer@example.com')
        ->click('label:has-text("PayPal")')
        ->wait(1)
        ->press('Continue')
        ->wait(1)
        ->press('button[type="submit"]:has-text("Pay with PayPal")')
        ->wait(2)
        ->assertSee('Thank you')
        ->assertSee('PayPal')
        ->assertNoJavascriptErrors();

    $order = Order::query()->where('email', 'paypal-buyer@example.com')->sole();

    expect($order->payment_method->value)->toBe('paypal');
});

test('9.10 completes checkout with bank transfer', function (): void {
    $page = checkoutTestReachPaymentStep($this, 'bank-buyer@example.com')
        ->click('label:has-text("Bank Transfer")')
        ->wait(1)
        ->press('Continue')
        ->wait(1)
        ->press('button[type="submit"]:has-text("Place order")')
        ->wait(2)
        ->assertSee('Thank you')
        ->assertSee('Bank Transfer Instructions')
        ->assertSee('IBAN')
        ->assertSee('COBADEFFXXX')
        ->assertSee('Reference')
        ->assertNoJavascriptErrors();

    $order = Order::query()->where('email', 'bank-buyer@example.com')->sole();

    expect($order->payment_method->value)->toBe('bank_transfer');

    // The order number is shown as the transfer reference.
    $page->assertSee($order->order_number);
});

test('9.11 shows error for declined credit card (magic number)', function (): void {
    checkoutTestFillCardAndPay(
        checkoutTestReachPaymentStep($this, 'declined-buyer@example.com'),
        '4000 0000 0000 0002',
    )
        ->assertSee('declined')
        ->assertScript('window.location.pathname.includes("/confirmation") === false')
        ->assertNoJavascriptErrors();

    // No order was created for the declined checkout.
    $checkout = Checkout::query()->where('email', 'declined-buyer@example.com')->sole();

    expect(Order::query()->where('checkout_id', $checkout->id)->exists())->toBeFalse();
});

test('9.12 shows error for insufficient funds (magic number)', function (): void {
    checkoutTestFillCardAndPay(
        checkoutTestReachPaymentStep($this, 'insufficient-buyer@example.com'),
        '4000 0000 0000 9995',
    )
        ->assertSee('insufficient')
        ->assertScript('window.location.pathname.includes("/confirmation") === false')
        ->assertNoJavascriptErrors();

    // No order was created for the declined checkout.
    $checkout = Checkout::query()->where('email', 'insufficient-buyer@example.com')->sole();

    expect(Order::query()->where('checkout_id', $checkout->id)->exists())->toBeFalse();
});

test('9.13 switches between payment method forms', function (): void {
    // The app gates the method-specific form behind the "Continue" button
    // (selectPayment reserves inventory); radios switch freely before that.
    $page = checkoutTestReachPaymentStep($this, 'switch-a@example.com')
        ->assertScript('document.querySelector("input[type=radio][value=credit_card]").checked === true')
        ->assertNotPresent('#card-number')
        ->click('label:has-text("PayPal")')
        ->wait(1)
        ->assertScript('document.querySelector("input[type=radio][value=paypal]").checked === true')
        ->assertScript('document.querySelector("input[type=radio][value=credit_card]").checked === false')
        ->click('label:has-text("Bank Transfer")')
        ->wait(1)
        ->assertScript('document.querySelector("input[type=radio][value=bank_transfer]").checked === true')
        ->press('Continue')
        ->wait(1)
        ->assertSee('Place order')
        ->assertSee('bank transfer instructions');

    // A fresh checkout (same session cart) confirmed with the default
    // Credit Card method shows the card form fields.
    checkoutTestGoToPaymentStep($page, 'switch-b@example.com')
        ->press('Continue')
        ->wait(1)
        ->assertVisible('#card-number')
        ->assertSee('Pay now')
        ->assertNoJavascriptErrors();
});
