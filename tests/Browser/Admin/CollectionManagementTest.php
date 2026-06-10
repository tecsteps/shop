<?php

it('shows the collection list with seeded collections', function (): void {
    $page = browserLoginAsAdmin();

    $page->navigate('/admin/collections')
        ->assertSeeIn('h1[data-flux-heading]', 'Collections')
        ->assertSee('T-Shirts')
        ->assertSee('New Arrivals')
        ->assertNoJavascriptErrors();
});

it('can create a new collection', function (): void {
    $page = browserLoginAsAdmin();

    $page->navigate('/admin/collections')
        ->click('@add-collection-button')
        ->assertSeeIn('h1[data-flux-heading]', 'Add collection')
        ->fill('title', 'E2E Test Collection')
        ->fill('@collection-description-input', 'A collection created by the E2E test suite.')
        ->click('@save-collection-button')
        ->assertSee('Collection saved')
        ->assertNoJavascriptErrors();

    $page->navigate('/admin/collections')
        ->assertSee('E2E Test Collection');
});

it('can edit a collection', function (): void {
    $page = browserLoginAsAdmin();

    $page->navigate('/admin/collections')
        ->assertSee('T-Shirts')
        ->click('T-Shirts')
        ->assertSeeIn('h1[data-flux-heading]', 'T-Shirts')
        ->fill('@collection-description-input', 'Updated description for T-Shirts collection.')
        ->click('@save-collection-button')
        ->assertSee('Collection saved')
        ->assertNoJavascriptErrors();
});
