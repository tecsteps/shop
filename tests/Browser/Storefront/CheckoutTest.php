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

function storefrontCheckoutHost(): array
{
    return ['host' => 'shop.test'];
}

function storefrontCheckoutStart(): mixed
{
    return visit('/products/classic-cotton-t-shirt', storefrontCheckoutHost())
        ->click('M')
        ->wait(1)
        ->click('Black')
        ->wait(1)
        ->click('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->wait(1)
        ->click('main button:has-text("Checkout")')
        ->wait(1)
        ->assertPathIs('/checkout')
        ->assertSee('Checkout');
}

/**
 * @param  array<string, string>  $overrides
 */
function storefrontCheckoutFillAddress(mixed $page, array $overrides = []): mixed
{
    $address = [
        'email' => 'test-buyer@example.com',
        'first_name' => 'Test',
        'last_name' => 'Buyer',
        'address1' => 'Teststrasse 1',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
    ];

    $address = array_replace($address, $overrides);

    return $page
        ->fill('input[wire\\:model="email"]', $address['email'])
        ->fill('input[wire\\:model="shippingAddress.first_name"]', $address['first_name'])
        ->fill('input[wire\\:model="shippingAddress.last_name"]', $address['last_name'])
        ->fill('input[wire\\:model="shippingAddress.address1"]', $address['address1'])
        ->fill('input[wire\\:model="shippingAddress.city"]', $address['city'])
        ->fill('input[wire\\:model="shippingAddress.postal_code"]', $address['postal_code'])
        ->select('select[wire\\:model="shippingAddress.country"]', $address['country']);
}

function storefrontCheckoutSubmitAddress(mixed $page): mixed
{
    return $page
        ->click('form[wire\\:submit="saveAddress"] button[type="submit"]')
        ->wait(1);
}

function storefrontCheckoutReachShipping(array $address = []): mixed
{
    return storefrontCheckoutSubmitAddress(
        storefrontCheckoutFillAddress(storefrontCheckoutStart(), $address),
    );
}

function storefrontCheckoutReachPayment(array $address = []): mixed
{
    return storefrontCheckoutReachShipping($address)
        ->click('button:has-text("Standard Shipping")')
        ->wait(1)
        ->click('button[wire\\:click="selectShippingMethod"]')
        ->wait(1)
        ->assertSee('Payment')
        ->assertSee('Pay now');
}

function storefrontCheckoutFillSuccessfulCard(mixed $page): mixed
{
    return $page
        ->fill('input[wire\\:model="cardNumber"]', '4242 4242 4242 4242')
        ->fill('input[wire\\:model="cardName"]', 'Test Buyer')
        ->fill('input[wire\\:model="cardExpiry"]', '12/28')
        ->fill('input[wire\\:model="cardCvc"]', '123');
}

function storefrontCheckoutApplyCartDiscount(string $code): mixed
{
    return visit('/products/classic-cotton-t-shirt', storefrontCheckoutHost())
        ->click('M')
        ->wait(1)
        ->click('Black')
        ->wait(1)
        ->click('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->wait(1)
        ->fill('input[wire\\:model="discountCode"]', $code)
        ->click('button:has-text("Apply")')
        ->wait(1);
}

test('completes full checkout with credit card', function (): void {
    storefrontCheckoutFillSuccessfulCard(storefrontCheckoutReachPayment())
        ->assertSee('29.98')
        ->click('button:has-text("Pay now")')
        ->wait(2)
        ->assertPathBeginsWith('/checkout/confirmation')
        ->assertSee('Thank you')
        ->assertSee('#1001')
        ->assertNoJavaScriptErrors();
});

test('shows shipping methods based on german address', function (): void {
    storefrontCheckoutReachShipping([
        'email' => 'test@example.com',
        'first_name' => 'Hans',
        'last_name' => 'Mueller',
        'address1' => 'Berliner Str. 10',
        'city' => 'Munich',
        'postal_code' => '80331',
        'country' => 'DE',
    ])
        ->assertSee('Standard Shipping')
        ->assertSee('4.99')
        ->assertNoJavaScriptErrors();
});

test('shows international shipping methods for non german address', function (): void {
    storefrontCheckoutReachShipping([
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Smith',
        'address1' => '123 Main St',
        'city' => 'New York',
        'postal_code' => '10001',
        'country' => 'US',
    ])
        ->assertSee('International Shipping')
        ->assertSee('14.99')
        ->assertNoJavaScriptErrors();
});

test('applies discount during checkout', function (): void {
    $page = storefrontCheckoutApplyCartDiscount('FLAT5')
        ->assertSee('FLAT5')
        ->click('main button:has-text("Checkout")')
        ->wait(1)
        ->assertPathIs('/checkout');

    storefrontCheckoutSubmitAddress(storefrontCheckoutFillAddress($page))
        ->click('button:has-text("Standard Shipping")')
        ->wait(1)
        ->click('button[wire\\:click="selectShippingMethod"]')
        ->wait(1)
        ->assertSee('FLAT5')
        ->assertSee('5.00')
        ->assertSee('24.98')
        ->assertNoJavaScriptErrors();
});

test('validates required contact email', function (): void {
    storefrontCheckoutStart()
        ->click('form[wire\\:submit="saveAddress"] button[type="submit"]')
        ->wait(1)
        ->assertSee('email field is required')
        ->assertNoJavaScriptErrors();
});

test('validates required shipping address fields', function (): void {
    storefrontCheckoutStart()
        ->fill('input[wire\\:model="email"]', 'test@example.com')
        ->click('form[wire\\:submit="saveAddress"] button[type="submit"]')
        ->wait(1)
        ->assertSee('first name field is required')
        ->assertSee('last name field is required')
        ->assertSee('address1 field is required')
        ->assertSee('city field is required')
        ->assertSee('postal code field is required')
        ->assertNoJavaScriptErrors();
});

test('validates invalid postal code format', function (): void {
    storefrontCheckoutSubmitAddress(storefrontCheckoutFillAddress(storefrontCheckoutStart(), [
        'postal_code' => 'INVALID',
    ]))
        ->assertSee('postal code format is invalid')
        ->assertNoJavaScriptErrors();
});

test('prevents checkout with empty cart', function (): void {
    visit('/cart', storefrontCheckoutHost())
        ->assertSee('Your cart is empty')
        ->assertButtonDisabled('main button:has-text("Checkout")')
        ->assertNoJavaScriptErrors();
});

test('completes checkout with paypal', function (): void {
    storefrontCheckoutReachPayment()
        ->select('select[wire\\:model\\.live="paymentMethod"]', 'paypal')
        ->wait(1)
        ->assertSee('Pay with PayPal')
        ->click('button:has-text("Pay with PayPal")')
        ->wait(2)
        ->assertPathBeginsWith('/checkout/confirmation')
        ->assertSee('Thank you')
        ->assertSee('PayPal')
        ->assertNoJavaScriptErrors();
});

test('completes checkout with bank transfer', function (): void {
    storefrontCheckoutReachPayment()
        ->select('select[wire\\:model\\.live="paymentMethod"]', 'bank_transfer')
        ->wait(1)
        ->assertSee('bank transfer instructions')
        ->click('button:has-text("Place order")')
        ->wait(2)
        ->assertPathBeginsWith('/checkout/confirmation')
        ->assertSee('Thank you')
        ->assertSee('IBAN')
        ->assertSee('BIC')
        ->assertSee('Reference')
        ->assertSee('#1001')
        ->assertNoJavaScriptErrors();
});

test('shows error for declined credit card', function (): void {
    storefrontCheckoutReachPayment()
        ->fill('input[wire\\:model="cardNumber"]', '4000 0000 0000 0002')
        ->fill('input[wire\\:model="cardName"]', 'Test Buyer')
        ->fill('input[wire\\:model="cardExpiry"]', '12/28')
        ->fill('input[wire\\:model="cardCvc"]', '123')
        ->click('button:has-text("Pay now")')
        ->wait(2)
        ->assertPathIs('/checkout')
        ->assertSee('declined')
        ->assertNoJavaScriptErrors();
});

test('shows error for insufficient funds', function (): void {
    storefrontCheckoutReachPayment()
        ->fill('input[wire\\:model="cardNumber"]', '4000 0000 0000 9995')
        ->fill('input[wire\\:model="cardName"]', 'Test Buyer')
        ->fill('input[wire\\:model="cardExpiry"]', '12/28')
        ->fill('input[wire\\:model="cardCvc"]', '123')
        ->click('button:has-text("Pay now")')
        ->wait(2)
        ->assertPathIs('/checkout')
        ->assertSee('insufficient')
        ->assertNoJavaScriptErrors();
});

test('switches between payment method forms', function (): void {
    storefrontCheckoutReachPayment()
        ->assertSee('Card number')
        ->assertSee('Pay now')
        ->select('select[wire\\:model\\.live="paymentMethod"]', 'paypal')
        ->wait(1)
        ->assertMissing('input[wire\\:model="cardNumber"]')
        ->assertSee('Pay with PayPal')
        ->select('select[wire\\:model\\.live="paymentMethod"]', 'bank_transfer')
        ->wait(1)
        ->assertSee('Place order')
        ->assertSee('bank transfer instructions')
        ->assertNoJavaScriptErrors();
});
