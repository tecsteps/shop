<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'About Us',
            'handle' => 'about',
            'body_html' => '<h2>About Acme Fashion</h2><p>We are a modern fashion retailer committed to bringing you the latest trends at affordable prices.</p><p>Founded in 2024, we have grown from a small online shop to a trusted destination for fashion enthusiasts.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'Contact',
            'handle' => 'contact',
            'body_html' => '<h2>Get in Touch</h2><p>Have a question? We would love to hear from you. Send us a message and we will respond as soon as possible.</p><p>Email: support@acme-fashion.test</p>',
        ]);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'Terms of Service',
            'handle' => 'terms-of-service',
            'body_html' => '<h2>Terms of Service</h2><p>These terms govern your use of our store.</p>',
            'status' => 'draft',
        ]);
    }
}
