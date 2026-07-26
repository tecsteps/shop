<?php

use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();
});

/**
 * Put a T-Shirt in the cart and land on the checkout contact step.
 */
function a11yTestOpenCheckout($page)
{
    return $page
        ->press('M')
        ->wait(1)
        ->press('button[aria-label="Black"]')
        ->wait(1)
        ->press('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->press('button:has-text("Checkout")')
        ->wait(1)
        ->assertPathIs('/checkout/new');
}

test('home page has no javascript errors or console warnings', function () {
    visit('/')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

test('home page has proper heading hierarchy', function () {
    visit('/')
        // Exactly one h1 on the page.
        ->assertScript("document.querySelectorAll('h1').length", 1)
        // The h1 carries the store name.
        ->assertScript("document.querySelector('h1').textContent.includes('Acme Fashion')")
        // The first heading in document order is the h1 (logical order).
        ->assertScript("document.querySelector('h1, h2').tagName === 'H1'")
        ->assertSee('Acme Fashion')
        ->assertNoJavascriptErrors();
});

test('product page has proper aria labels for variant selector', function () {
    visit('/products/classic-cotton-t-shirt')
        ->assertSee('Size')
        ->assertSee('Color')
        ->assertSee('Add to cart')
        // Color swatches expose an accessible name via aria-label.
        ->assertPresent('button[aria-label="Black"]')
        ->assertPresent('button[aria-label="White"]')
        ->assertPresent('button[aria-label="Navy"]')
        // Option buttons expose pressed state.
        ->assertAttribute('button:has-text("M")', 'aria-pressed', 'false')
        ->assertNoJavascriptErrors();
});

test('product page images have alt text', function () {
    // The seeder intentionally creates no ProductMedia records, so the
    // gallery renders an aria-hidden placeholder. The alt-text invariant is
    // asserted for any rendered image (thumbnails inside labelled buttons
    // are decorative), and the placeholder must be hidden from AT.
    visit('/products/classic-cotton-t-shirt')
        ->assertScript(
            'Array.from(document.querySelectorAll(\'section[aria-label="Product images"] img\')).filter((img) => ! img.closest(\'button\')).every((img) => (img.getAttribute(\'alt\') ?? \'\').trim().length > 0)'
        )
        ->assertScript(
            'document.querySelector(\'section[aria-label="Product images"] [aria-hidden="true"]\') !== null || document.querySelectorAll(\'section[aria-label="Product images"] img\').length > 0'
        )
        ->assertNoJavascriptErrors();
});

test('customer login form has accessible labels', function () {
    visit('/account/login')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertScript("document.querySelector('#email').labels.length > 0")
        ->assertScript("document.querySelector('#password').labels.length > 0")
        ->assertNoJavascriptErrors();
});

test('admin login form has accessible labels', function () {
    visit('/admin/login')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertScript("document.querySelector('#email').labels.length > 0")
        ->assertScript("document.querySelector('#password').labels.length > 0")
        ->assertNoJavascriptErrors();
});

test('checkout form has accessible labels', function () {
    a11yTestOpenCheckout(visit('/products/classic-cotton-t-shirt'))
        ->assertSee('Email')
        ->assertScript("document.querySelector('#checkout-email').labels.length > 0")
        ->assertScript(
            "['address-first_name', 'address-last_name', 'address-address1', 'address-city', 'address-postal_code', 'address-country_code'].every((id) => document.getElementById(id).labels.length > 0)"
        )
        ->assertNoJavascriptErrors();
});

test('checkout validation errors are accessible', function () {
    $page = a11yTestOpenCheckout(visit('/products/classic-cotton-t-shirt'));

    // Native HTML5 constraint validation would block an empty submit before
    // Livewire runs, so drop the required attributes to exercise the
    // server-side (accessible) error rendering.
    $page
        ->assertScript(
            "(() => { document.querySelectorAll('form input[required]').forEach((el) => el.removeAttribute('required')); return true; })()"
        )
        ->press('Continue to shipping')
        ->waitForText('The email field is required.')
        ->assertSee('The email field is required.')
        // Errors render in containers linked to their inputs via aria-describedby.
        ->assertPresent('#checkout-email-error')
        ->assertAttribute('#checkout-email', 'aria-describedby', 'checkout-email-error')
        ->assertPresent('#address-first_name-error')
        ->assertAttribute('#address-first_name', 'aria-describedby', 'address-first_name-error')
        ->assertNoJavascriptErrors();
});

test('can navigate storefront with keyboard only', function () {
    visit('/')
        // First Tab focuses the skip link...
        ->keys('body', 'Tab')
        ->assertScript("document.activeElement !== null && document.activeElement.textContent.trim() === 'Skip to main content'")
        // ...and it becomes visible (sr-only until focused: 1px wide).
        ->assertScript('document.activeElement.getBoundingClientRect().width > 10')
        // Enter activates the focused link.
        ->keys('body', 'Enter')
        ->wait(1)
        ->assertFragmentIs('main-content')
        ->assertNoJavascriptErrors();
});

test('cart page has no console errors or warnings', function () {
    visit('/cart')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

test('search page has proper form labels', function () {
    visit('/search?q=shirt')
        ->assertSee('Classic Cotton T-Shirt')
        // The search input has an associated (visually hidden) label.
        ->assertScript(
            "(() => { const input = document.querySelector('#search-page-input'); return input.labels.length > 0 || input.getAttribute('aria-label') !== null; })()"
        )
        ->assertNoJavascriptErrors();
});
