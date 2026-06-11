<?php

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\NavigationService;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Facades\Cache;

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

it('repairs stale Acme Fashion preview theme and navigation seed data', function () {
    $context = createStoreContext([
        'name' => 'Acme Fashion',
        'handle' => 'acme-fashion',
    ]);
    $store = $context['store'];

    $theme = Theme::factory()->for($store)->create(['name' => 'Default Theme']);
    ThemeSettings::factory()
        ->for($theme)
        ->withSettings([
            'primary_color' => '#1a1a2e',
            'secondary_color' => '#0f45e6',
            'hero_heading' => 'Welcome to Acme Fashion',
        ])
        ->create();

    foreach ([
        'New Arrivals' => 'new-arrivals',
        'T-Shirts' => 't-shirts',
        'Pants & Jeans' => 'pants-jeans',
        'Sale' => 'sale',
    ] as $title => $handle) {
        Collection::factory()->for($store)->create([
            'title' => $title,
            'handle' => $handle,
        ]);
    }

    foreach ([
        'Classic Cotton T-Shirt' => 'classic-cotton-t-shirt',
        'Graphic Print Tee' => 'graphic-print-tee',
        'V-Neck Linen Tee' => 'v-neck-linen-tee',
        'Striped Polo Shirt' => 'striped-polo-shirt',
    ] as $title => $handle) {
        Product::factory()->for($store)->create([
            'title' => $title,
            'handle' => $handle,
            'status' => 'active',
            'published_at' => now(),
        ]);
    }

    foreach ([
        'About Us' => 'about',
        'FAQ' => 'faq',
        'Shipping & Returns' => 'shipping-returns',
        'Privacy Policy' => 'privacy-policy',
        'Terms of Service' => 'terms',
    ] as $title => $handle) {
        Page::factory()->for($store)->create([
            'title' => $title,
            'handle' => $handle,
        ]);
    }

    $menu = NavigationMenu::factory()->for($store)->create([
        'handle' => 'main-menu',
        'title' => 'Main Menu',
    ]);

    NavigationItem::factory()->for($menu, 'menu')->create([
        'label' => 'Home',
        'type' => NavigationItemType::Link,
        'url' => '/',
        'position' => 0,
    ]);

    foreach (['New Arrivals', 'T-Shirts', 'Pants & Jeans', 'Sale'] as $position => $label) {
        NavigationItem::factory()->for($menu, 'menu')->create([
            'label' => $label,
            'type' => NavigationItemType::Collection,
            'url' => null,
            'resource_id' => null,
            'position' => $position + 1,
        ]);
    }

    Cache::put("theme_settings:{$store->getKey()}", ['secondary_color' => '#0f45e6'], now()->addMinutes(5));
    Cache::put("navigation_tree:{$store->getKey()}:main-menu", [['label' => 'Home']], now()->addMinutes(5));

    $migration = require database_path('migrations/2026_06_11_000001_repair_acme_fashion_storefront_seed_data.php');
    $migration->up();
    $collectionProductsMigration = require database_path('migrations/2026_06_11_000002_repair_acme_fashion_collection_products.php');
    $collectionProductsMigration->up();

    app()->instance('current_store', $store->fresh());

    expect(app(ThemeSettingsService::class)->all($store)['secondary_color'])->toBe('#e94560');
    expect(array_column(app(NavigationService::class)->tree('main-menu'), 'label'))
        ->toBe(['Home', 'New Arrivals', 'T-Shirts', 'Pants & Jeans', 'Sale']);
    expect(StoreDomain::query()->where('hostname', '2026-06-09-claude-code-fable-5.agentic-engineers.dev')->exists())
        ->toBeTrue();
    expect(Collection::query()->where('handle', 't-shirts')->firstOrFail()->products()->pluck('products.handle')->all())
        ->toBe([
            'classic-cotton-t-shirt',
            'graphic-print-tee',
            'v-neck-linen-tee',
            'striped-polo-shirt',
        ]);
});
