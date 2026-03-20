<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();

        $publishedAt = now()->subMonths(3)->toIso8601String();

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'About Us',
            'handle' => 'about',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Our Story</h2><p>Acme Fashion was founded with a simple mission: to bring modern, high-quality fashion to everyone. Based in Berlin, we curate collections that blend timeless style with contemporary trends.</p><p>Our philosophy is rooted in the belief that great fashion should be accessible, sustainable, and made to last.</p><h2>Our Values</h2><p>We are committed to ethical sourcing, sustainable materials, and fair labor practices across our entire supply chain.</p><h2>Our Team</h2><p>Our Berlin-based team of designers and fashion enthusiasts work tirelessly to bring you the best curated selection of clothing and accessories.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'FAQ',
            'handle' => 'faq',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Standard shipping within Germany takes 2-4 business days. Express shipping arrives in 1-2 business days. EU orders typically arrive within 5-7 business days.</p><h3>What is your return policy?</h3><p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, and in their original packaging with all tags attached.</p><h3>Do you ship internationally?</h3><p>Yes! We ship to all EU countries as well as the US, UK, Canada, and Australia.</p><h3>How can I track my order?</h3><p>Once your order has been shipped, you will receive an email with a tracking number. You can use this number to track your package on the carrier\'s website.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'Shipping & Returns',
            'handle' => 'shipping-returns',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Shipping</h2><h3>Shipping Rates</h3><ul><li>Germany Standard: 4.99 EUR (2-4 business days)</li><li>Germany Express: 9.99 EUR (1-2 business days)</li><li>EU Standard: 8.99 EUR (5-7 business days)</li><li>International: 14.99 EUR (7-14 business days)</li></ul><h2>Returns</h2><p>We offer a 30-day return policy on all items. Items must be in their original condition with tags attached. Customer pays return shipping unless the item is defective.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'Privacy Policy',
            'handle' => 'privacy-policy',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Privacy Policy</h2><h3>Information We Collect</h3><p>We collect information you provide directly, including name, email, shipping address, and payment details when placing an order.</p><h3>How We Use Your Information</h3><p>Your information is used to process orders, send shipping updates, and improve our services.</p><h3>Cookies</h3><p>We use cookies to enhance your browsing experience and analyze site traffic.</p><h3>Contact</h3><p>For privacy-related inquiries, please contact privacy@acme-fashion.test.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'Terms of Service',
            'handle' => 'terms',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Terms of Service</h2><h3>Orders and Payments</h3><p>All prices are displayed in EUR and include applicable taxes. We accept credit cards, PayPal, and bank transfers.</p><h3>Product Descriptions</h3><p>We strive for accuracy in our product descriptions. Actual colors may vary slightly due to monitor settings.</p><h3>Limitation of Liability</h3><p>Acme Fashion shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or services.</p><h3>Governing Law</h3><p>These terms are governed by the laws of the Federal Republic of Germany.</p>',
        ]);
    }
}
