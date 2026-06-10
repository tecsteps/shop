<?php

it('shows the analytics dashboard', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Analytics")')
        ->assertSeeIn('h1[data-flux-heading]', 'Analytics')
        ->assertNoJavascriptErrors();
});

it('shows sales data', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Analytics")')
        ->assertSeeIn('[data-test="analytics-kpi-orders"]', 'Orders')
        ->assertSee('Revenue')
        ->assertNoJavascriptErrors();
});

it('shows conversion funnel data', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Analytics")')
        ->assertVisible('[data-test="analytics-funnel"]')
        ->assertSee('Visits')
        ->assertNoJavascriptErrors();
});
