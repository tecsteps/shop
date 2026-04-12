<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $pages = [
            [
                'handle' => 'about-us',
                'title' => 'About Us',
                'body_html' => '<h2>Welcome</h2><p>We are a demo store built on the shop platform.</p>',
            ],
            [
                'handle' => 'contact',
                'title' => 'Contact',
                'body_html' => '<h2>Get in touch</h2><p>Email us at support@shop.test.</p>',
            ],
            [
                'handle' => 'faq',
                'title' => 'FAQ',
                'body_html' => '<h2>Frequently Asked Questions</h2><p>How do I place an order? Just add products to your cart and check out.</p>',
            ],
        ];

        foreach ($pages as $data) {
            Page::query()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $data['handle']],
                [
                    'title' => $data['title'],
                    'body_html' => $data['body_html'],
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );
        }

        $menu = NavigationMenu::query()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'main-menu'],
            ['title' => 'Main Menu']
        );

        $items = [
            ['label' => 'Home', 'type' => 'link', 'url' => '/', 'resource_id' => null],
            ['label' => 'Collections', 'type' => 'link', 'url' => '/collections', 'resource_id' => null],
            ['label' => 'About', 'type' => 'link', 'url' => '/pages/about-us', 'resource_id' => null],
            ['label' => 'Contact', 'type' => 'link', 'url' => '/pages/contact', 'resource_id' => null],
        ];

        foreach ($items as $position => $item) {
            $exists = $menu->items()->where('label', $item['label'])->exists();

            if (! $exists) {
                NavigationItem::create([
                    'menu_id' => $menu->id,
                    'type' => $item['type'],
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'resource_id' => $item['resource_id'],
                    'position' => $position,
                ]);
            }
        }

        Discount::query()->firstOrCreate(
            ['store_id' => $store->id, 'code' => 'WELCOME10'],
            [
                'type' => 'code',
                'value_type' => 'percent',
                'value_amount' => 10,
                'status' => 'active',
                'usage_count' => 0,
            ]
        );

        Discount::query()->firstOrCreate(
            ['store_id' => $store->id, 'code' => 'FREESHIP'],
            [
                'type' => 'code',
                'value_type' => 'free_shipping',
                'value_amount' => 0,
                'status' => 'active',
                'usage_count' => 0,
            ]
        );
    }
}
