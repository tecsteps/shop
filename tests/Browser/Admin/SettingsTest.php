<?php

use App\Models\ShippingZone;

it('can view store settings', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Settings")')
        ->assertSeeIn('h1[data-flux-heading]', 'Store Settings')
        ->assertValue('@store-name-input', 'Acme Fashion')
        ->assertNoJavascriptErrors();
});

it('can update store name', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Settings")')
        ->fill('@store-name-input', 'Acme Fashion Updated')
        ->click('@save-general-settings-button')
        ->assertSee('Settings saved')
        ->assertNoJavascriptErrors();

    $page->navigate('/admin/settings')
        ->assertValue('@store-name-input', 'Acme Fashion Updated');
});

it('can view shipping zones', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Settings")')
        ->click('@settings-tab-shipping')
        ->assertSee('Domestic')
        ->assertSee('Standard Shipping')
        ->assertSee('4.99')
        ->assertNoJavascriptErrors();
});

it('can add a new shipping rate to existing zone', function (): void {
    $domesticZoneId = ShippingZone::query()
        ->withoutGlobalScopes()
        ->whereRelation('store', 'handle', 'acme-fashion')
        ->where('name', 'Domestic')
        ->firstOrFail()
        ->getKey();

    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Settings")')
        ->click('@settings-tab-shipping')
        ->assertSee('Domestic')
        ->click('@add-rate-'.$domesticZoneId)
        ->assertSee('Add shipping rate')
        ->fill('@rate-name-input', 'Overnight Shipping')
        ->fill('@rate-flat-amount-input', '14.99')
        ->click('@save-rate-button')
        ->assertSee('Shipping rate saved')
        ->assertSee('Overnight Shipping')
        ->assertSee('14.99')
        ->assertNoJavascriptErrors();
});

it('can view tax settings', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Settings")')
        ->click('@settings-tab-taxes')
        ->assertSee('Tax Settings')
        ->assertNoJavascriptErrors();
});

it('can update tax inclusion setting', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Settings")')
        ->click('@settings-tab-taxes')
        ->click('@prices-include-tax-switch')
        ->click('@save-tax-settings-button')
        ->assertSee('Tax settings saved')
        ->assertNoJavascriptErrors();
});

it('can view domain settings', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Settings")')
        ->click('@settings-tab-domains')
        ->assertSee('acme-fashion.test')
        ->assertNoJavascriptErrors();
});
