<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

test('shows the collection list with seeded collections', function () {
    visit('/admin/collections')
        ->assertSee('Collections')
        ->assertSee('T-Shirts')
        ->assertSee('New Arrivals')
        ->assertNoJavascriptErrors();
});

test('can create a new collection', function () {
    $page = visit('/admin/collections');

    // The spec calls the button "Create collection"; the UI labels it "Add collection".
    $page->press('main a:has-text("Add collection")')
        ->waitForText('Search products')
        ->fill('title', 'E2E Test Collection')
        ->fill('descriptionHtml', 'A collection created by the E2E test suite.')
        ->press('button:has-text("Save")')
        ->waitForText('Collection saved')
        ->assertSee('Collection saved')
        ->assertNoJavascriptErrors();

    // Fresh visit instead of the sidebar link: wire:navigate may restore a
    // cached snapshot of the list that predates the creation.
    visit('/admin/collections')
        ->waitForText('E2E Test Collection')
        ->assertSee('E2E Test Collection')
        ->assertNoJavascriptErrors();
});

test('can edit a collection', function () {
    $page = visit('/admin/collections');

    $page->press('table a:has-text("T-Shirts")')
        ->waitForText('Search products')
        ->fill('descriptionHtml', 'Updated description for T-Shirts collection.')
        ->press('button:has-text("Save")')
        ->waitForText('Collection saved')
        ->assertSee('Collection saved')
        ->assertNoJavascriptErrors();
});
