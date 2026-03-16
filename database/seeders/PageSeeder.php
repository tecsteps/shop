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
            'handle' => 'about-us',
            'body_html' => '<h2>About Acme Store</h2><p>We are a premium e-commerce store offering the finest products at competitive prices. Our mission is to provide exceptional quality and outstanding customer service.</p><p>Founded in 2024, we have been serving customers worldwide with a curated selection of products.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'Contact Us',
            'handle' => 'contact-us',
            'body_html' => '<h2>Get in Touch</h2><p>We would love to hear from you. Reach out to us at support@acme.test or visit our store.</p><h3>Business Hours</h3><p>Monday - Friday: 9:00 AM - 5:00 PM<br>Saturday - Sunday: Closed</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'Shipping Policy',
            'handle' => 'shipping-policy',
            'body_html' => '<h2>Shipping Policy</h2><p>We offer free standard shipping on all orders over 50 EUR. Orders are typically processed within 1-2 business days.</p><h3>Shipping Methods</h3><ul><li>Standard Shipping (5-7 business days)</li><li>Express Shipping (2-3 business days)</li></ul>',
        ]);
    }
}
