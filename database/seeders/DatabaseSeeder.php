<?php

namespace Database\Seeders;

use App\Enums\NavigationItemType;
use App\Enums\PageStatus;
use App\Enums\ThemeStatus;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Default Organization',
            'billing_email' => 'billing@shop.test',
        ]);

        $store = Store::create([
            'organization_id' => $organization->id,
            'name' => 'Default Store',
            'handle' => 'default-store',
            'status' => 'active',
            'default_currency' => 'USD',
            'default_locale' => 'en',
            'timezone' => 'UTC',
        ]);

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => 'shop.test',
            'type' => 'storefront',
            'is_primary' => true,
            'tls_mode' => 'managed',
        ]);

        StoreSettings::create([
            'store_id' => $store->id,
            'settings_json' => [],
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@shop.test',
            'status' => 'active',
        ]);

        $store->users()->attach($admin->id, ['role' => 'owner']);

        // Create default theme
        $theme = Theme::create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
            'status' => ThemeStatus::Published,
            'published_at' => now()->toIso8601String(),
        ]);

        ThemeSettings::create([
            'theme_id' => $theme->id,
            'settings_json' => [
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over $50',
                    'link' => '/collections',
                    'background_color' => '#1a1a1a',
                ],
                'sticky_header' => true,
                'logo_url' => null,
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#6b7280',
                    'accent' => '#f59e0b',
                ],
                'social_links' => [
                    'facebook' => 'https://facebook.com',
                    'instagram' => 'https://instagram.com',
                    'twitter' => 'https://x.com',
                    'tiktok' => null,
                    'youtube' => null,
                ],
                'dark_mode' => 'system',
                'home_sections' => [
                    ['type' => 'hero', 'enabled' => true],
                    ['type' => 'featured_collections', 'enabled' => true],
                    ['type' => 'featured_products', 'enabled' => true],
                    ['type' => 'newsletter', 'enabled' => true],
                    ['type' => 'rich_text', 'enabled' => false],
                ],
                'hero' => [
                    'heading' => 'Welcome to Default Store',
                    'subheading' => 'Discover amazing products at great prices.',
                    'cta_text' => 'Shop now',
                    'cta_link' => '/collections',
                    'background_image' => null,
                ],
                'footer' => [
                    'store_address' => '123 Commerce St, Shop City, SC 12345',
                    'contact_email' => 'hello@shop.test',
                ],
            ],
        ]);

        // Create sample pages
        $aboutPage = Page::create([
            'store_id' => $store->id,
            'title' => 'About Us',
            'handle' => 'about',
            'body_html' => '<h2>Our Story</h2><p>We are a passionate team dedicated to bringing you the best products at the best prices. Our mission is to make quality products accessible to everyone.</p><h2>Our Values</h2><p>Quality, affordability, and customer satisfaction are at the heart of everything we do.</p>',
            'status' => PageStatus::Published,
            'published_at' => now()->toIso8601String(),
        ]);

        $contactPage = Page::create([
            'store_id' => $store->id,
            'title' => 'Contact Us',
            'handle' => 'contact',
            'body_html' => '<h2>Get in Touch</h2><p>We would love to hear from you! Whether you have a question about products, orders, or anything else, our team is ready to answer all your questions.</p><h3>Email</h3><p>hello@shop.test</p><h3>Address</h3><p>123 Commerce St, Shop City, SC 12345</p>',
            'status' => PageStatus::Published,
            'published_at' => now()->toIso8601String(),
        ]);

        // Create Main Menu navigation
        $mainMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => 'main-menu',
            'title' => 'Main Menu',
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Home',
            'url' => '/',
            'position' => 0,
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Collections',
            'url' => '/collections',
            'position' => 1,
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'About',
            'resource_id' => $aboutPage->id,
            'position' => 2,
        ]);

        NavigationItem::create([
            'menu_id' => $mainMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'Contact',
            'resource_id' => $contactPage->id,
            'position' => 3,
        ]);

        // Create Footer Menu navigation
        $footerMenu = NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'About Us',
            'resource_id' => $aboutPage->id,
            'position' => 0,
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Page,
            'label' => 'Contact Us',
            'resource_id' => $contactPage->id,
            'position' => 1,
        ]);

        NavigationItem::create([
            'menu_id' => $footerMenu->id,
            'type' => NavigationItemType::Link,
            'label' => 'Search',
            'url' => '/search',
            'position' => 2,
        ]);
    }
}
