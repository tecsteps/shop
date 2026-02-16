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
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        Page::create([
            'store_id' => $store->id,
            'title' => 'About Us',
            'handle' => 'about',
            'content' => '<p>We are Acme Fashion, dedicated to bringing you the finest curated collection of apparel and accessories. Our team is passionate about quality, sustainability, and style.</p><p>Founded in 2024, we have grown from a small boutique to a trusted destination for fashion-forward shoppers worldwide.</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);

        Page::create([
            'store_id' => $store->id,
            'title' => 'Contact Us',
            'handle' => 'contact',
            'content' => '<p>We would love to hear from you! Reach out to us at <strong>hello@acmefashion.test</strong> or use the form below.</p><p>Our support team is available Monday through Friday, 9am to 5pm EST.</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }
}
