<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function adminAnalyticsBrowserHost(): array
{
    return ['host' => 'shop.test'];
}

function adminAnalyticsBrowserAuthenticate(mixed $testCase): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);

    return $store;
}

function adminAnalyticsBrowserOpenAnalytics(mixed $testCase): mixed
{
    adminAnalyticsBrowserAuthenticate($testCase);

    return visit('/admin', adminAnalyticsBrowserHost())
        ->wait(1)
        ->assertPathIs('/admin')
        ->assertSee('Dashboard')
        ->click('a[href$="/admin/analytics"]')
        ->wait(1)
        ->assertPathIs('/admin/analytics')
        ->assertSee('Analytics')
        ->assertNoJavaScriptErrors();
}

test('shows the analytics dashboard', function (): void {
    adminAnalyticsBrowserOpenAnalytics($this)
        ->assertSee('Analytics')
        ->assertNoJavaScriptErrors();
});

test('shows sales data', function (): void {
    adminAnalyticsBrowserOpenAnalytics($this)
        ->assertSee('Orders')
        ->assertSee('Revenue')
        ->assertNoJavaScriptErrors();
});

test('shows conversion funnel data', function (): void {
    adminAnalyticsBrowserOpenAnalytics($this)
        ->assertSee('Visits')
        ->assertNoJavaScriptErrors();
});
