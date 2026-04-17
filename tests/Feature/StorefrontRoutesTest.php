<?php

use App\Enums\PageStatus;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);

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
});

it('renders the home page', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/');

    $response->assertSuccessful();
});

it('renders the collections index page', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/collections');

    $response->assertSuccessful();
});

it('renders a CMS page', function () {
    Page::factory()->published()->create([
        'store_id' => $this->store->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'body_html' => '<p>About our store</p>',
    ]);

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/pages/about-us');

    $response->assertSuccessful()
        ->assertSee('About Us')
        ->assertSee('About our store');
});

it('returns 404 for non-existent page', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/pages/nonexistent');

    $response->assertNotFound();
});

it('returns 404 for draft page', function () {
    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'draft-page',
        'status' => PageStatus::Draft,
    ]);

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/pages/draft-page');

    $response->assertNotFound();
});

it('renders the storefront layout with store name', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/');

    $response->assertSuccessful()
        ->assertSee($this->store->name);
});
