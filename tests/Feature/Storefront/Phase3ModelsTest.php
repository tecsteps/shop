<?php

use App\Enums\NavigationItemType;
use App\Enums\PageStatus;
use App\Enums\ThemeStatus;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use App\Services\NavigationService;
use App\Services\ThemeSettingsService;

it('can create a theme with settings and files', function (): void {
    $ctx = createStoreContext();

    $theme = Theme::factory()->published()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'My Theme',
    ]);

    $settings = ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
    ]);

    $file = ThemeFile::factory()->create([
        'theme_id' => $theme->id,
        'path' => 'templates/index.blade.php',
    ]);

    expect($theme->name)->toBe('My Theme')
        ->and($theme->status)->toBe(ThemeStatus::Published)
        ->and($theme->settings)->not->toBeNull()
        ->and($theme->settings->settings_json)->toBeArray()
        ->and($theme->files)->toHaveCount(1)
        ->and($theme->files->first()->path)->toBe('templates/index.blade.php');
});

it('can create pages with published status', function (): void {
    $ctx = createStoreContext();

    $page = Page::factory()->published()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'body_html' => '<p>About us content</p>',
    ]);

    expect($page->title)->toBe('About Us')
        ->and($page->handle)->toBe('about-us')
        ->and($page->status)->toBe(PageStatus::Published)
        ->and($page->published_at)->not->toBeNull()
        ->and($page->body_html)->toContain('About us content');
});

it('can create navigation menus with items', function (): void {
    $ctx = createStoreContext();

    $menu = NavigationMenu::factory()->mainMenu()->create([
        'store_id' => $ctx['store']->id,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'Home',
        'url' => '/',
        'position' => 0,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'About',
        'url' => '/pages/about',
        'position' => 1,
    ]);

    $menu->refresh();

    expect($menu->handle)->toBe('main-menu')
        ->and($menu->items)->toHaveCount(2)
        ->and($menu->items->first()->label)->toBe('Home')
        ->and($menu->items->last()->label)->toBe('About');
});

it('builds navigation tree via service', function (): void {
    $ctx = createStoreContext();

    $menu = NavigationMenu::factory()->mainMenu()->create([
        'store_id' => $ctx['store']->id,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'Shop',
        'url' => '/collections',
        'position' => 0,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'Contact',
        'url' => '/pages/contact',
        'position' => 1,
    ]);

    $service = new NavigationService;
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Shop')
        ->and($tree[0]['url'])->toBe('/collections')
        ->and($tree[1]['label'])->toBe('Contact')
        ->and($tree[1]['url'])->toBe('/pages/contact');
});

it('loads active theme settings via service', function (): void {
    $ctx = createStoreContext();

    $theme = Theme::factory()->published()->create([
        'store_id' => $ctx['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'announcement_bar' => ['enabled' => true, 'text' => 'Test announcement'],
            'sticky_header' => true,
        ],
    ]);

    $service = new ThemeSettingsService;

    expect($service->get('announcement_bar.text'))->toBe('Test announcement')
        ->and($service->get('sticky_header'))->toBeTrue()
        ->and($service->get('nonexistent', 'fallback'))->toBe('fallback')
        ->and($service->all())->toHaveKey('announcement_bar');
});

it('scopes themes to store via BelongsToStore', function (): void {
    $ctxA = createStoreContext('theme-store-a.test');
    $ctxB = createStoreContext('theme-store-b.test');

    Theme::factory()->create(['store_id' => $ctxA['store']->id, 'name' => 'Theme A']);
    Theme::factory()->create(['store_id' => $ctxB['store']->id, 'name' => 'Theme B']);

    app()->instance('current_store', $ctxA['store']);

    expect(Theme::count())->toBe(1)
        ->and(Theme::first()->name)->toBe('Theme A');
});

it('scopes pages to store via BelongsToStore', function (): void {
    $ctxA = createStoreContext('page-store-a.test');
    $ctxB = createStoreContext('page-store-b.test');

    Page::factory()->create(['store_id' => $ctxA['store']->id, 'title' => 'Page A']);
    Page::factory()->create(['store_id' => $ctxB['store']->id, 'title' => 'Page B']);

    app()->instance('current_store', $ctxA['store']);

    expect(Page::count())->toBe(1)
        ->and(Page::first()->title)->toBe('Page A');
});

it('scopes navigation menus to store via BelongsToStore', function (): void {
    $ctxA = createStoreContext('nav-store-a.test');
    $ctxB = createStoreContext('nav-store-b.test');

    NavigationMenu::factory()->create(['store_id' => $ctxA['store']->id, 'title' => 'Menu A']);
    NavigationMenu::factory()->create(['store_id' => $ctxB['store']->id, 'title' => 'Menu B']);

    app()->instance('current_store', $ctxA['store']);

    expect(NavigationMenu::count())->toBe(1)
        ->and(NavigationMenu::first()->title)->toBe('Menu A');
});
