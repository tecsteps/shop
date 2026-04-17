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
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $pages = [
            [
                'title' => 'About Us',
                'handle' => 'about',
                'status' => PageStatus::Published,
                'published_at' => now(),
                'content_html' => '<h2>About Acme Fashion</h2><p>We are a premium fashion brand dedicated to delivering high-quality clothing at affordable prices. Our mission is to make stylish, sustainable fashion accessible to everyone.</p>',
            ],
            [
                'title' => 'Contact',
                'handle' => 'contact',
                'status' => PageStatus::Published,
                'published_at' => now(),
                'content_html' => '<h2>Contact Us</h2><p>Have questions? Reach out to us at support@acme-fashion.test or call us at +1 (555) 123-4567.</p>',
            ],
            [
                'title' => 'FAQ',
                'handle' => 'faq',
                'status' => PageStatus::Published,
                'published_at' => now(),
                'content_html' => '<h2>Frequently Asked Questions</h2><p><strong>What is your return policy?</strong></p><p>We offer a 30-day return policy on all unworn items.</p><p><strong>How long does shipping take?</strong></p><p>Standard shipping takes 3-5 business days.</p>',
            ],
            [
                'title' => 'Terms of Service',
                'handle' => 'terms',
                'status' => PageStatus::Draft,
                'content_html' => '<h2>Terms of Service</h2><p>By using our website, you agree to these terms and conditions.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                ...$page,
            ]);
        }
    }
}
