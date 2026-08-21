<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
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
        foreach (Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get() as $store) {
            foreach ([
                ['title' => 'About us', 'handle' => 'about', 'content' => '<h2>Made for everyday life</h2><p>We choose useful, durable products and make shopping simple.</p>'],
                ['title' => 'Shipping & returns', 'handle' => 'shipping-returns', 'content' => '<h2>Shipping & returns</h2><p>Orders ship promptly from our warehouse. Contact support if you need help with a return.</p>'],
                ['title' => 'Contact', 'handle' => 'contact', 'content' => '<h2>Contact us</h2><p>Our support team is happy to help with your order or product questions.</p>'],
            ] as $page) {
                Page::withoutGlobalScopes()->updateOrCreate(
                    ['store_id' => $store->getKey(), 'handle' => $page['handle']],
                    [...$page, 'store_id' => $store->getKey(), 'body_html' => $page['content'], 'status' => PageStatus::Published, 'published_at' => now()],
                );
            }
        }
    }
}
