<?php

it('shows the pages list', function (): void {
    $page = browserLoginAsAdmin();

    $page->navigate('/admin/pages')
        ->assertSeeIn('h1[data-flux-heading]', 'Pages')
        ->assertSee('About')
        ->assertNoJavascriptErrors();
});

it('can create a new page', function (): void {
    $page = browserLoginAsAdmin();

    $page->navigate('/admin/pages')
        ->click('@add-page-button')
        ->assertSeeIn('h1[data-flux-heading]', 'Add page')
        ->fill('title', 'FAQ')
        ->fill('@page-handle-input', 'faq-e2e')
        ->fill('@page-body-input', 'Frequently asked questions content here.')
        ->click('@save-page-button')
        ->assertSee('Page saved')
        ->assertNoJavascriptErrors();
});

it('can edit an existing page', function (): void {
    $page = browserLoginAsAdmin();

    $page->navigate('/admin/pages')
        ->assertSee('About')
        ->click('About Us')
        ->fill('@page-body-input', 'Updated about page content.')
        ->click('@save-page-button')
        ->assertSee('Page saved')
        ->assertNoJavascriptErrors();
});
