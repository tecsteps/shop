<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

test('shows the pages list', function () {
    visit('/admin/pages')
        ->assertSee('Pages')
        ->assertSee('About')
        ->assertNoJavascriptErrors();
});

test('can create a new page', function () {
    $page = visit('/admin/pages');

    // The spec calls the button "Create page"; the UI labels it "Add page".
    // The spec's title "FAQ" collides with the seeded FAQ page (handle must
    // be unique), so a distinct title is used.
    $page->press('main a:has-text("Add page")')
        ->waitForText('Set automatically when publishing')
        ->fill('title', 'FAQ E2E')
        ->fill('bodyHtml', 'Frequently asked questions content here.')
        ->press('button:has-text("Save")')
        ->waitForText('Page saved')
        ->assertSee('Page saved')
        ->assertNoJavascriptErrors();

    // Fresh visit instead of the sidebar link: wire:navigate may restore a
    // cached snapshot of the list that predates the creation.
    visit('/admin/pages')
        ->waitForText('FAQ E2E')
        ->assertSee('FAQ E2E')
        ->assertNoJavascriptErrors();
});

test('can edit an existing page', function () {
    $page = visit('/admin/pages');

    $page->press('table a:has-text("About")')
        ->waitForText('Set automatically when publishing')
        ->fill('bodyHtml', 'Updated about page content.')
        ->press('button:has-text("Save")')
        ->waitForText('Page saved')
        ->assertSee('Page saved')
        ->assertNoJavascriptErrors();
});
