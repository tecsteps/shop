<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->get()->each(function (Store $store): void {
            foreach ($this->pagesFor($store) as $page) {
                Page::withoutGlobalScopes()->updateOrCreate(
                    [
                        'store_id' => $store->getKey(),
                        'handle' => $page['handle'],
                    ],
                    [
                        'title' => $page['title'],
                        'body_html' => $page['body_html'],
                        'status' => 'published',
                        'published_at' => now(),
                    ],
                );
            }
        });
    }

    /**
     * @return array<int, array{handle: string, title: string, body_html: string}>
     */
    private function pagesFor(Store $store): array
    {
        return [
            [
                'handle' => 'about',
                'title' => 'About',
                'body_html' => "<p>{$store->name} is the demo storefront for this self-contained shop platform.</p><p>The catalog includes multi-variant products, sale pricing, inventory rules, backorder handling, and digital product examples for checkout and storefront testing.</p>",
            ],
            [
                'handle' => 'faq',
                'title' => 'FAQ',
                'body_html' => '<p>Shipping, payment, returns, and account flows are implemented progressively according to the project roadmap.</p>',
            ],
            [
                'handle' => 'shipping',
                'title' => 'Shipping',
                'body_html' => '<p>Shipping zones, rates, and tax-aware checkout calculations are seeded in the checkout phase.</p>',
            ],
        ];
    }
}
