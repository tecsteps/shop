<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->first();

        if (! $store) {
            return;
        }

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'About Us',
            'handle' => 'about-us',
            'body_html' => '<h2>About Acme Fashion</h2><p>We are a leading fashion retailer committed to quality and style.</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'Contact',
            'handle' => 'contact',
            'body_html' => '<h2>Contact Us</h2><p>Get in touch with our team at support@acme.com.</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'Shipping Policy',
            'handle' => 'shipping-policy',
            'body_html' => '<h2>Shipping Policy</h2><p>We offer free standard shipping on orders over $50.</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }
}
