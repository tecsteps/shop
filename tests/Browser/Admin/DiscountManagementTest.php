<?php

it('shows seeded discount codes', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Discounts")')
        ->assertSeeIn('h1[data-flux-heading]', 'Discounts')
        ->assertSee('WELCOME10')
        ->assertSee('FLAT5')
        ->assertSee('FREESHIP')
        ->assertNoJavascriptErrors();
});

it('can create a new percentage discount code', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Discounts")')
        ->click('@create-discount-button')
        ->assertSeeIn('h1[data-flux-heading]', 'Create discount')
        ->fill('@discount-code-input', 'E2ETEST25')
        ->click('@value-type-percent')
        ->fill('@discount-value-input', '25')
        ->fill('@starts-at-input', '2026-01-01T00:00')
        ->fill('@ends-at-input', '2026-12-31T23:59')
        ->click('@save-discount-button')
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors()
        ->click('aside a:has-text("Discounts")')
        ->assertSee('E2ETEST25');
});

it('can create a fixed amount discount code', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Discounts")')
        ->click('@create-discount-button')
        ->fill('@discount-code-input', 'E2EFLAT10')
        ->click('@value-type-fixed')
        ->fill('@discount-value-input', '10.00')
        ->fill('@starts-at-input', '2026-01-01T00:00')
        ->click('@save-discount-button')
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors();
});

it('can create a free shipping discount code', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Discounts")')
        ->click('@create-discount-button')
        ->fill('@discount-code-input', 'E2EFREESHIP')
        ->click('@value-type-free-shipping')
        ->fill('@starts-at-input', '2026-01-01T00:00')
        ->click('@save-discount-button')
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors();
});

it('can edit a discount', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Discounts")')
        ->assertSee('WELCOME10')
        ->click('WELCOME10')
        ->assertSeeIn('h1[data-flux-heading]', 'WELCOME10')
        ->fill('@discount-value-input', '15')
        ->click('@save-discount-button')
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors();
});

it('shows discount status indicators', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Discounts")')
        ->assertVisible('table tr:has-text("WELCOME10") [data-flux-badge]:has-text("Active")')
        ->assertVisible('table tr:has-text("EXPIRED20") [data-flux-badge]:has-text("Expired")')
        ->assertNoJavascriptErrors();
});
