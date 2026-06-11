<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repair stale demo storefront data on already-provisioned preview
     * databases. Fresh installs hit this migration before seed data exists,
     * so it intentionally no-ops unless the Acme Fashion store is present.
     */
    public function up(): void
    {
        $store = DB::table('stores')->where('handle', 'acme-fashion')->first(['id']);

        if ($store === null) {
            return;
        }

        $storeId = (int) $store->id;

        $this->ensurePreviewDomain($storeId);
        $this->repairPublishedTheme($storeId);
        $this->repairNavigation($storeId);

        Cache::forget("theme_settings:{$storeId}");
        Cache::forget("navigation_tree:{$storeId}:main-menu");
        Cache::forget("navigation_tree:{$storeId}:footer-menu");
    }

    /**
     * Reverse is intentionally empty: this is an idempotent data correction for
     * preview/demo seed drift and should not remove user-visible storefront data.
     */
    public function down(): void
    {
        //
    }

    private function ensurePreviewDomain(int $storeId): void
    {
        $hosts = [
            '2026-06-09-claude-code-fable-5.agentic-engineers.dev',
            parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ];

        foreach (array_filter(array_unique($hosts)) as $hostname) {
            if (in_array($hostname, ['localhost', '127.0.0.1', '::1'], true)) {
                continue;
            }

            DB::table('store_domains')->updateOrInsert(
                ['hostname' => strtolower($hostname)],
                [
                    'store_id' => $storeId,
                    'type' => 'storefront',
                    'is_primary' => false,
                    'tls_mode' => 'managed',
                    'created_at' => now(),
                ],
            );

            Cache::forget('store_domain:'.strtolower($hostname));
        }
    }

    private function repairPublishedTheme(int $storeId): void
    {
        $now = now();
        $theme = DB::table('themes')
            ->where('store_id', $storeId)
            ->where('name', 'Default Theme')
            ->first(['id']);

        if ($theme === null) {
            $themeId = DB::table('themes')->insertGetId([
                'store_id' => $storeId,
                'name' => 'Default Theme',
                'version' => '1.0.0',
                'status' => 'published',
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $themeId = (int) $theme->id;

            DB::table('themes')
                ->where('id', $themeId)
                ->update([
                    'version' => '1.0.0',
                    'status' => 'published',
                    'published_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        DB::table('theme_settings')->updateOrInsert(
            ['theme_id' => $themeId],
            [
                'settings_json' => json_encode($this->fashionThemeSettings(), JSON_THROW_ON_ERROR),
                'updated_at' => $now,
            ],
        );
    }

    private function repairNavigation(int $storeId): void
    {
        $this->seedMenu($storeId, 'main-menu', 'Main Menu', [
            ['label' => 'Home', 'type' => 'link', 'url' => '/'],
            ['label' => 'New Arrivals', 'type' => 'collection', 'handle' => 'new-arrivals'],
            ['label' => 'T-Shirts', 'type' => 'collection', 'handle' => 't-shirts'],
            ['label' => 'Pants & Jeans', 'type' => 'collection', 'handle' => 'pants-jeans'],
            ['label' => 'Sale', 'type' => 'collection', 'handle' => 'sale'],
        ]);

        $this->seedMenu($storeId, 'footer-menu', 'Footer Menu', [
            ['label' => 'About Us', 'type' => 'page', 'handle' => 'about'],
            ['label' => 'FAQ', 'type' => 'page', 'handle' => 'faq'],
            ['label' => 'Shipping & Returns', 'type' => 'page', 'handle' => 'shipping-returns'],
            ['label' => 'Privacy Policy', 'type' => 'page', 'handle' => 'privacy-policy'],
            ['label' => 'Terms of Service', 'type' => 'page', 'handle' => 'terms'],
        ]);
    }

    /**
     * @param  list<array{label: string, type: string, url?: string, handle?: string}>  $items
     */
    private function seedMenu(int $storeId, string $handle, string $title, array $items): void
    {
        $now = now();
        $menu = DB::table('navigation_menus')
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->first(['id']);

        if ($menu === null) {
            $menuId = DB::table('navigation_menus')->insertGetId([
                'store_id' => $storeId,
                'handle' => $handle,
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $menuId = (int) $menu->id;

            DB::table('navigation_menus')
                ->where('id', $menuId)
                ->update(['title' => $title, 'updated_at' => $now]);
        }

        foreach ($items as $position => $item) {
            $resourceId = $this->resourceId($storeId, $item);

            DB::table('navigation_items')->updateOrInsert(
                [
                    'menu_id' => $menuId,
                    'label' => $item['label'],
                ],
                [
                    'type' => $item['type'],
                    'url' => $item['url'] ?? null,
                    'resource_id' => $resourceId,
                    'position' => $position,
                ],
            );
        }
    }

    /**
     * @param  array{type: string, handle?: string}  $item
     */
    private function resourceId(int $storeId, array $item): ?int
    {
        if (($item['handle'] ?? null) === null) {
            return null;
        }

        $table = match ($item['type']) {
            'collection' => 'collections',
            'page' => 'pages',
            default => null,
        };

        if ($table === null) {
            return null;
        }

        $id = DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $item['handle'])
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function fashionThemeSettings(): array
    {
        return [
            'primary_color' => '#1a1a2e',
            'secondary_color' => '#e94560',
            'font_family' => 'Inter, sans-serif',
            'hero_heading' => 'Welcome to Acme Fashion',
            'hero_subheading' => 'Discover our curated collection of modern essentials',
            'hero_cta_text' => 'Shop New Arrivals',
            'hero_cta_link' => '/collections/new-arrivals',
            'featured_collection_handles' => ['new-arrivals', 't-shirts', 'sale'],
            'featured_products_collection_handle' => 'new-arrivals',
            'footer_text' => '2025 Acme Fashion. All rights reserved.',
            'show_announcement_bar' => true,
            'announcement_text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
            'products_per_page' => 12,
            'show_vendor' => true,
            'show_quantity_selector' => true,
        ];
    }
};
