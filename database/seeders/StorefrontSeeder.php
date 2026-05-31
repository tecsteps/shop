<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Enums\PageStatus;
use App\Enums\ThemeStatus;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Database\Seeder;

/**
 * Seeds the storefront presentation layer for the demo store: a published theme
 * with settings, the main + footer navigation menus, and a couple of CMS pages.
 *
 * Builds on {@see DemoStoreSeeder::store()} so it shares the canonical demo
 * tenant (host shop.test). Re-running is safe (firstOrCreate throughout).
 */
class StorefrontSeeder extends Seeder
{
    public const THEME_NAME = 'Default';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = (new DemoStoreSeeder)->store();

        // Bind the store so BelongsToStore auto-fills store_id and the
        // StoreScope resolves while we seed.
        app()->instance('current_store', $store);

        $theme = $this->seedTheme($store);
        $this->seedNavigation($store);
        $this->seedPages($store);

        // The theme settings cache may hold a pre-seed (default) snapshot.
        app(ThemeSettingsService::class)->forget($store->id);
    }

    /**
     * Create the published theme + settings for the store.
     */
    private function seedTheme(Store $store): Theme
    {
        $theme = Theme::firstOrCreate(
            ['store_id' => $store->id, 'name' => self::THEME_NAME],
            [
                'version' => '1.0.0',
                'status' => ThemeStatus::Published->value,
                'published_at' => now(),
            ],
        );

        ThemeSettings::firstOrCreate(
            ['theme_id' => $theme->id],
            ['settings_json' => $this->settings($store)],
        );

        return $theme;
    }

    /**
     * The demo theme settings: announcement bar, hero, social links, footer.
     *
     * @return array<string, mixed>
     */
    private function settings(Store $store): array
    {
        return array_replace_recursive(ThemeSettingsService::defaults(), [
            'announcement' => [
                'enabled' => true,
                'text' => 'Free shipping on orders over 50.00 '.$store->default_currency,
                'background_color' => '#171717',
            ],
            'home' => [
                'hero' => [
                    'heading' => 'New season, new style',
                    'subheading' => 'Explore the latest arrivals from '.$store->name.'.',
                    'cta_label' => 'Shop the collection',
                    'cta_url' => '/collections',
                ],
            ],
            'footer' => [
                'description' => $store->name.' — quality goods, delivered.',
                'social' => [
                    'instagram' => 'https://instagram.com',
                    'facebook' => 'https://facebook.com',
                ],
            ],
        ]);
    }

    /**
     * Seed the main + footer navigation menus with items.
     */
    private function seedNavigation(Store $store): void
    {
        $mainMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main menu'],
        );

        if ($mainMenu->items()->doesntExist()) {
            $this->item($mainMenu, 'Home', '/', 0);
            $shop = $this->item($mainMenu, 'Shop', '/collections', 1);
            $this->item($mainMenu, 'About', '/pages/about', 2);
            $this->item($mainMenu, 'Contact', '/pages/contact', 3);

            // One level of dropdown under "Shop".
            $this->item($mainMenu, 'All products', '/collections', 0, $shop->id);
        }

        $footerMenu = NavigationMenu::firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'footer-menu'],
            ['title' => 'Footer menu'],
        );

        if ($footerMenu->items()->doesntExist()) {
            $shop = $this->item($footerMenu, 'Shop', null, 0);
            $this->item($footerMenu, 'Collections', '/collections', 0, $shop->id);

            $info = $this->item($footerMenu, 'Information', null, 1);
            $this->item($info->menu, 'About', '/pages/about', 0, $info->id);
            $this->item($info->menu, 'Contact', '/pages/contact', 1, $info->id);
        }
    }

    /**
     * Idempotently create a navigation item.
     */
    private function item(NavigationMenu $menu, string $label, ?string $url, int $position, ?int $parentId = null): NavigationItem
    {
        return NavigationItem::firstOrCreate(
            ['menu_id' => $menu->id, 'parent_id' => $parentId, 'label' => $label],
            [
                'type' => NavigationItemType::Link->value,
                'url' => $url,
                'position' => $position,
            ],
        );
    }

    /**
     * Seed the standard CMS pages (About, Contact).
     */
    private function seedPages(Store $store): void
    {
        $pages = [
            'about' => [
                'title' => 'About us',
                'body_html' => '<p>Welcome to '.e($store->name).'. We are passionate about bringing you high-quality products and a delightful shopping experience.</p>',
            ],
            'contact' => [
                'title' => 'Contact',
                'body_html' => '<p>Questions? Reach us any time and our team will get back to you within one business day.</p>',
            ],
        ];

        foreach ($pages as $handle => $data) {
            Page::firstOrCreate(
                ['store_id' => $store->id, 'handle' => $handle],
                [
                    'title' => $data['title'],
                    'body_html' => $data['body_html'],
                    'status' => PageStatus::Published->value,
                    'published_at' => now(),
                ],
            );
        }
    }
}
