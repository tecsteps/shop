<?php

use App\Enums\PageStatus;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use App\Services\NavigationService;
use App\Services\ThemeSettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('theme page and navigation tables and seeded fixtures exist', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Schema::hasColumns('themes', ['store_id', 'name', 'version', 'status', 'published_at']))->toBeTrue()
        ->and(Schema::hasColumns('theme_files', ['theme_id', 'path', 'storage_key', 'sha256', 'byte_size']))->toBeTrue()
        ->and(Schema::hasColumns('theme_settings', ['theme_id', 'settings_json', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('pages', ['store_id', 'title', 'handle', 'body_html', 'status', 'published_at']))->toBeTrue()
        ->and(Schema::hasColumns('navigation_menus', ['store_id', 'handle', 'title']))->toBeTrue()
        ->and(Schema::hasColumns('navigation_items', ['menu_id', 'parent_id', 'type', 'label', 'url', 'resource_id', 'position']))->toBeTrue()
        ->and(Theme::withoutGlobalScopes()->count())->toBe(2)
        ->and(ThemeFile::withoutGlobalScopes()->count())->toBe(6)
        ->and(ThemeSettings::withoutGlobalScopes()->count())->toBe(2)
        ->and(Page::withoutGlobalScopes()->count())->toBe(6)
        ->and(NavigationMenu::withoutGlobalScopes()->count())->toBe(4)
        ->and(NavigationItem::withoutGlobalScopes()->count())->toBeGreaterThanOrEqual(10);
});

test('theme page and navigation models are scoped to the resolved store', function () {
    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();

    Theme::factory()->published()->create(['store_id' => $firstStore->getKey(), 'name' => 'First Theme']);
    Theme::factory()->published()->create(['store_id' => $secondStore->getKey(), 'name' => 'Second Theme']);
    Page::factory()->published()->create(['store_id' => $firstStore->getKey(), 'handle' => 'first-page']);
    Page::factory()->published()->create(['store_id' => $secondStore->getKey(), 'handle' => 'second-page']);
    NavigationMenu::factory()->create(['store_id' => $firstStore->getKey(), 'handle' => 'first-menu']);
    NavigationMenu::factory()->create(['store_id' => $secondStore->getKey(), 'handle' => 'second-menu']);

    app()->instance('current_store', $firstStore);

    expect(Theme::query()->pluck('name')->all())->toBe(['First Theme'])
        ->and(Page::query()->pluck('handle')->all())->toBe(['first-page'])
        ->and(NavigationMenu::query()->pluck('handle')->all())->toBe(['first-menu']);
});

test('theme and navigation child models are scoped through their parent store', function () {
    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();
    $firstTheme = Theme::factory()->published()->create(['store_id' => $firstStore->getKey()]);
    $secondTheme = Theme::factory()->published()->create(['store_id' => $secondStore->getKey()]);
    $firstMenu = NavigationMenu::factory()->create(['store_id' => $firstStore->getKey()]);
    $secondMenu = NavigationMenu::factory()->create(['store_id' => $secondStore->getKey()]);

    ThemeFile::factory()->create(['theme_id' => $firstTheme->getKey(), 'path' => 'first.blade.php']);
    ThemeFile::factory()->create(['theme_id' => $secondTheme->getKey(), 'path' => 'second.blade.php']);
    ThemeSettings::factory()->create(['theme_id' => $firstTheme->getKey()]);
    ThemeSettings::factory()->create(['theme_id' => $secondTheme->getKey()]);
    NavigationItem::factory()->create(['menu_id' => $firstMenu->getKey(), 'label' => 'First']);
    NavigationItem::factory()->create(['menu_id' => $secondMenu->getKey(), 'label' => 'Second']);

    app()->instance('current_store', $firstStore);

    expect(ThemeFile::query()->pluck('path')->all())->toBe(['first.blade.php'])
        ->and(ThemeSettings::query()->count())->toBe(1)
        ->and(NavigationItem::query()->pluck('label')->all())->toBe(['First']);
});

test('theme settings service loads cached published settings with defaults', function () {
    $this->seed(DatabaseSeeder::class);

    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $settings = app(ThemeSettingsService::class)->forStore($store);

    expect($settings['announcement']['text'])->toBe('Free shipping on orders over 75.00 EUR')
        ->and($settings['home']['hero']['heading'])->toBe('Acme Fashion')
        ->and(Cache::has("theme_settings:{$store->getKey()}"))->toBeTrue();
});

test('navigation service resolves resource URLs for seeded menus', function () {
    $this->seed(DatabaseSeeder::class);

    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $menu = NavigationMenu::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'main-menu')
        ->firstOrFail();

    $items = app(NavigationService::class)->buildTree($menu);
    $shop = collect($items)->firstWhere('label', 'Shop');
    $shopChildren = collect($shop['children']);

    expect(collect($items)->pluck('label')->all())->toContain('Home', 'Shop', 'About')
        ->and($shop['url'])->toBe('/collections')
        ->and($shopChildren->pluck('label')->all())->toContain('New Arrivals', 'T-Shirts')
        ->and($shopChildren->firstWhere('label', 'New Arrivals')['url'])->toBe('/collections/new-arrivals')
        ->and(collect($items)->firstWhere('label', 'About')['url'])->toBe('/pages/about');
});

test('storefront pages render published database content only', function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);

    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

    Page::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'title' => 'Private Draft',
        'handle' => 'private-draft',
        'body_html' => '<p>This should not render.</p>',
        'status' => PageStatus::Draft,
        'published_at' => null,
    ]);

    $this->withHeader('Host', 'shop.test')
        ->get('/pages/about')
        ->assertSuccessful()
        ->assertSee('About')
        ->assertSee('Acme Fashion is the demo storefront');

    $this->withHeader('Host', 'shop.test')
        ->get('/pages/private-draft')
        ->assertNotFound();
});
