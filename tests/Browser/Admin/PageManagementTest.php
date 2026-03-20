<?php

// Suite 17: Admin Pages - CMS page management

it('shows pages list', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Pages")')
        ->assertSee('About Us')
        ->assertNoJavaScriptErrors();
});

it('can create a new page', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Pages")')
        ->click('New page')
        ->fill('title', 'E2E Test Page')
        ->click('Create page')
        ->assertSee('Page created')
        ->assertNoJavaScriptErrors();
});

it('can edit a page', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Pages")')
        ->click('About Us')
        ->clear('title')
        ->fill('title', 'About Us Updated')
        ->click('Save changes')
        ->assertSee('Page updated')
        ->assertNoJavaScriptErrors();
});
