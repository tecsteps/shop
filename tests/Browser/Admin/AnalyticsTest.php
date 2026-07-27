<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

test('shows the analytics dashboard', function () {
    visit('/admin/analytics')
        ->assertSee('Analytics')
        ->assertNoJavascriptErrors();
});

test('shows sales data', function () {
    visit('/admin/analytics')
        ->assertSee('Orders')
        ->assertSee('Revenue')
        ->assertNoJavascriptErrors();
});

test('shows conversion funnel data', function () {
    visit('/admin/analytics')
        ->assertSee('Visits')
        ->assertNoJavascriptErrors();
});
