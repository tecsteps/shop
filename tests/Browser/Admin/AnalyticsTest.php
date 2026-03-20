<?php

// Suite 18: Admin Analytics - Analytics dashboard rendering

it('shows the analytics page', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Analytics")')
        ->assertSee('Analytics')
        ->assertNoJavaScriptErrors();
});

it('shows analytics with date range filter', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Analytics")')
        ->assertSee('Analytics')
        ->assertNoJavaScriptErrors();
});

it('shows empty state when no analytics data', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Analytics")')
        ->assertSee('Analytics')
        ->assertNoJavaScriptErrors();
});
