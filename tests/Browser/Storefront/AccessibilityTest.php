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

function storefrontAccessibilityHost(): array
{
    return ['host' => 'shop.test'];
}

function storefrontAccessibilityAddClassicToCart(): mixed
{
    return visit('/products/classic-cotton-t-shirt', storefrontAccessibilityHost())
        ->click('M')
        ->wait(1)
        ->click('Black')
        ->wait(1)
        ->click('Add to cart')
        ->wait(1);
}

function storefrontAccessibilityCheckoutStart(): mixed
{
    return storefrontAccessibilityAddClassicToCart()
        ->navigate('/cart')
        ->wait(1)
        ->click('main button:has-text("Checkout")')
        ->wait(1)
        ->assertPathIs('/checkout');
}

test('home page has no javascript errors or console warnings', function (): void {
    visit('/', storefrontAccessibilityHost())
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertNoAccessibilityIssues();
});

test('home page has proper heading hierarchy', function (): void {
    visit('/', storefrontAccessibilityHost())
        ->assertSee('Acme Fashion')
        ->assertScript('document.querySelectorAll("h1").length === 1')
        ->assertScript('document.querySelector("h1").textContent.includes("Acme Fashion")')
        ->assertScript(<<<'JS'
function() {
    let previous = 0;

    for (const heading of document.querySelectorAll('h1, h2, h3, h4, h5, h6')) {
        const level = Number(heading.tagName.slice(1));

        if (previous !== 0 && level > previous + 1) {
            return false;
        }

        previous = level;
    }

    return true;
}
JS)
        ->assertNoJavaScriptErrors();
});

test('product page has proper aria labels for variant selectors', function (): void {
    visit('/products/classic-cotton-t-shirt', storefrontAccessibilityHost())
        ->assertSee('Size')
        ->assertSee('Color')
        ->assertVisible('button:has-text("Add to cart")')
        ->assertButtonEnabled('button:has-text("Add to cart")')
        ->assertScript(<<<'JS'
function() {
    const optionGroups = Array.from(document.querySelectorAll('main fieldset'));

    return optionGroups.length >= 2
        && optionGroups.every((group) => group.querySelector('legend')?.textContent.trim().length > 0)
        && optionGroups.every((group) => Array.from(group.querySelectorAll('button')).every((button) => button.hasAttribute('aria-pressed')));
}
JS)
        ->assertNoJavaScriptErrors();
});

test('product page images or placeholders have accessible text', function (): void {
    visit('/products/classic-cotton-t-shirt', storefrontAccessibilityHost())
        ->assertScript(<<<'JS'
function() {
    const images = Array.from(document.querySelectorAll('main img'));

    if (images.length > 0) {
        return images.every((image) => image.getAttribute('alt')?.trim().length > 0);
    }

    const placeholder = document.querySelector('[data-test="product-image-placeholder"]');

    return placeholder?.getAttribute('role') === 'img'
        && placeholder.getAttribute('aria-label')?.includes('Classic Cotton T-Shirt');
}
JS)
        ->assertNoJavaScriptErrors();
});

test('customer login form has accessible labels', function (): void {
    visit('/account/login', storefrontAccessibilityHost())
        ->assertSee('Email address')
        ->assertSee('Password')
        ->assertScript(<<<'JS'
function() {
    return Array.from(document.querySelectorAll('form input')).every((input) => {
        return input.labels.length > 0
            || input.getAttribute('aria-label')
            || input.closest('[data-flux-field]')?.querySelector('[data-flux-label]');
    });
}
JS)
        ->assertNoJavaScriptErrors();
});

test('admin login form has accessible labels', function (): void {
    visit('/admin/login', storefrontAccessibilityHost())
        ->assertSee('Email address')
        ->assertSee('Password')
        ->assertScript(<<<'JS'
function() {
    return Array.from(document.querySelectorAll('form input')).every((input) => {
        return input.labels.length > 0
            || input.getAttribute('aria-label')
            || input.closest('[data-flux-field]')?.querySelector('[data-flux-label]');
    });
}
JS)
        ->assertNoJavaScriptErrors();
});

test('checkout form has accessible labels', function (): void {
    storefrontAccessibilityCheckoutStart()
        ->assertSee('Email')
        ->assertScript(<<<'JS'
function() {
    return Array.from(document.querySelectorAll('form[wire\\:submit="saveAddress"] input, form[wire\\:submit="saveAddress"] select')).every((control) => {
        return control.labels.length > 0
            || control.getAttribute('aria-label')
            || control.closest('[data-flux-field]')?.querySelector('[data-flux-label]');
    });
}
JS)
        ->assertNoJavaScriptErrors();
});

test('checkout validation errors are accessible', function (): void {
    storefrontAccessibilityCheckoutStart()
        ->click('form[wire\\:submit="saveAddress"] button[type="submit"]')
        ->wait(1)
        ->assertSee('email field is required')
        ->assertScript(<<<'JS'
function() {
    const email = document.querySelector('input[wire\\:model="email"]');
    const describedBy = email?.getAttribute('aria-describedby');
    const error = describedBy ? document.getElementById(describedBy) : null;

    return email?.getAttribute('aria-invalid') === 'true'
        && Boolean(error)
        && error.textContent.includes('email field is required');
}
JS)
        ->assertNoJavaScriptErrors();
});

test('can navigate storefront with keyboard only', function (): void {
    $page = visit('/', storefrontAccessibilityHost());

    $page->script('() => document.querySelector(\'a[href="#main-content"]\').focus()');

    $page->assertScript('document.activeElement.textContent.includes("Skip to main content")');

    $page->script('() => document.querySelector(\'nav[aria-label="Main navigation"] a[href$="/collections"]\').focus()');

    $page
        ->wait(0.2)
        ->assertScript('document.activeElement.matches(\'nav[aria-label="Main navigation"] a[href$="/collections"]\')')
        ->keys('nav[aria-label="Main navigation"] a[href$="/collections"]', 'Enter')
        ->wait(1)
        ->assertPathIs('/collections')
        ->assertNoJavaScriptErrors();
});

test('cart page has no console errors or warnings', function (): void {
    visit('/cart', storefrontAccessibilityHost())
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertNoAccessibilityIssues();
});

test('search page has proper form labels', function (): void {
    visit('/search?q=shirt', storefrontAccessibilityHost())
        ->assertSee('Search results')
        ->assertScript('document.querySelector("main input[aria-label=\"Search products\"]") !== null')
        ->assertNoJavaScriptErrors();
});
