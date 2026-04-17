<?php

use App\Models\NavigationMenu;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();

    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);

    $theme = Theme::factory()->published()->create([
        'store_id' => $this->store->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
        'title' => 'Main Menu',
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'footer-menu',
        'title' => 'Footer Menu',
    ]);

    app()->instance('current_store', $this->store);
});

// --- Accessibility ---

it('storefront layout has skip-to-content link', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/');

    $response->assertSuccessful()
        ->assertSee('Skip to main content')
        ->assertSee('id="main-content"', false);
});

it('storefront layout has aria labels on navigation', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/');

    $response->assertSuccessful()
        ->assertSee('aria-label="Main navigation"', false)
        ->assertSee('aria-label="Mobile navigation"', false);
});

it('storefront header buttons have aria labels', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/');

    $response->assertSuccessful()
        ->assertSee('aria-label="Search"', false)
        ->assertSee('aria-label="Open cart"', false)
        ->assertSee('aria-label="Account"', false);
});

// --- Error Pages ---

it('renders styled 404 page', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/nonexistent-page-xyz');

    $response->assertStatus(404)
        ->assertSee('404')
        ->assertSee('Page not found')
        ->assertSee('Go back home');
});

// --- Structured Logging ---

it('has structured json logging channel configured', function () {
    $channels = config('logging.channels');

    expect($channels)->toHaveKey('structured')
        ->and($channels['structured']['driver'])->toBe('single')
        ->and($channels['structured']['formatter'])->toBe(\Monolog\Formatter\JsonFormatter::class);
});

// --- Dark Mode Support ---

it('storefront layout has dark mode classes', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/');

    $response->assertSuccessful()
        ->assertSee('dark:bg-gray-950', false)
        ->assertSee('dark:text-gray-300', false);
});

it('404 page has dark mode classes', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/nonexistent-page-xyz');

    $response->assertStatus(404)
        ->assertSee('dark:bg-gray-950', false)
        ->assertSee('dark:text-white', false);
});
