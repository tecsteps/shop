<?php

use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\NavigationService;
use App\Services\ThemeSettingsService;

it('returns defaults when the store has no published theme', function () {
    createStoreContext();

    $settings = app(ThemeSettingsService::class)->all();

    expect($settings['hero_heading'])->toBe(ThemeSettingsService::defaults()['hero_heading']);
    expect($settings['products_per_page'])->toBe(12);
});

it('merges stored settings of the active theme over the defaults', function () {
    $context = createStoreContext();

    $theme = Theme::factory()->for($context['store'])->create();
    ThemeSettings::factory()->for($theme)->withSettings(['hero_heading' => 'Custom Heading'])->create();

    $service = app(ThemeSettingsService::class);

    expect($service->get('hero_heading'))->toBe('Custom Heading');
    expect($service->get('products_per_page'))->toBe(12);
});

it('ignores draft themes when loading settings', function () {
    $context = createStoreContext();

    $theme = Theme::factory()->draft()->for($context['store'])->create();
    ThemeSettings::factory()->for($theme)->withSettings(['hero_heading' => 'Draft Heading'])->create();

    expect(app(ThemeSettingsService::class)->get('hero_heading'))
        ->toBe(ThemeSettingsService::defaults()['hero_heading']);
});

it('invalidates the settings cache when theme settings are updated', function () {
    $context = createStoreContext();

    $theme = Theme::factory()->for($context['store'])->create();
    $settings = ThemeSettings::factory()->for($theme)->withSettings(['hero_heading' => 'Before'])->create();

    $service = app(ThemeSettingsService::class);
    expect($service->get('hero_heading'))->toBe('Before');

    $settings->update(['settings_json' => ['hero_heading' => 'After']]);

    expect($service->get('hero_heading'))->toBe('After');
});

it('builds a navigation tree with resolved urls for every item type', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->for($context['store'])->create(['handle' => 'summer']);
    $page = Page::factory()->for($context['store'])->create(['handle' => 'about']);
    $product = Product::factory()->active()->for($context['store'])->create(['handle' => 'tee']);

    $menu = NavigationMenu::factory()->for($context['store'])->create(['handle' => 'main-menu']);

    NavigationItem::factory()->for($menu, 'menu')->create(['label' => 'Home', 'url' => '/', 'position' => 0]);
    NavigationItem::factory()->for($menu, 'menu')->collection($collection->getKey())->create(['label' => 'Summer', 'position' => 1]);
    NavigationItem::factory()->for($menu, 'menu')->page($page->getKey())->create(['label' => 'About', 'position' => 2]);
    NavigationItem::factory()->for($menu, 'menu')->product($product->getKey())->create(['label' => 'Tee', 'position' => 3]);

    $tree = app(NavigationService::class)->buildTree($menu);

    expect(array_column($tree, 'url'))->toBe(['/', '/collections/summer', '/pages/about', '/products/tee']);
    expect(array_column($tree, 'label'))->toBe(['Home', 'Summer', 'About', 'Tee']);
});

it('omits navigation items whose linked resource was deleted', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->for($context['store'])->create(['handle' => 'gone']);
    $menu = NavigationMenu::factory()->for($context['store'])->create();
    NavigationItem::factory()->for($menu, 'menu')->collection($collection->getKey())->create(['label' => 'Gone']);

    $collection->delete();

    expect(app(NavigationService::class)->buildTree($menu))->toBe([]);
});

it('resolves a single navigation item url', function () {
    $context = createStoreContext();

    $page = Page::factory()->for($context['store'])->create(['handle' => 'faq']);
    $menu = NavigationMenu::factory()->for($context['store'])->create();
    $item = NavigationItem::factory()->for($menu, 'menu')->page($page->getKey())->create();

    expect(app(NavigationService::class)->resolveUrl($item))->toBe('/pages/faq');
});
