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
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        $pages = [
            'about' => [
                'title' => 'About Us',
                'body' => '<h2>Our Story</h2><p>Acme Fashion started in Berlin with a simple mission: timeless essentials made to last. We design every piece in-house and work directly with family-owned factories.</p><h2>Our Values</h2><p>We believe in ethical sourcing, sustainable materials, and fair labor. Every supplier in our chain is audited annually.</p><h2>Our Team</h2><p>Our Berlin-based team of designers, tailors, and customer advocates is passionate about quality clothing that respects people and the planet.</p>',
            ],
            'faq' => [
                'title' => 'FAQ',
                'body' => '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Germany: 2-4 business days standard, 1-2 days express. EU: 5-7 business days.</p><h3>What is your return policy?</h3><p>30 days from delivery. Items must be unworn and in original packaging.</p><h3>Do you ship internationally?</h3><p>Yes, we ship to the EU plus the US, UK, Canada, and Australia.</p><h3>How do I track my order?</h3><p>You will receive an email with a tracking number as soon as your order ships.</p>',
            ],
            'shipping-returns' => [
                'title' => 'Shipping & Returns',
                'body' => '<h2>Shipping Rates</h2><h3>Germany</h3><ul><li>Standard (2-4 days): 4.99 EUR</li><li>Express (1-2 days): 9.99 EUR</li></ul><h3>European Union</h3><ul><li>EU Standard (5-7 days): 8.99 EUR</li></ul><h3>International</h3><ul><li>International (7-14 days): 14.99 EUR</li></ul><h2>Returns</h2><p>Returns are accepted within 30 days of delivery. Customers pay return shipping unless the item is defective.</p>',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'body' => '<h2>Information We Collect</h2><p>We collect your contact details, order history, and browsing behavior to operate the store.</p><h2>How We Use Your Information</h2><p>We use your data to fulfil orders, provide support, and improve our store.</p><h2>Cookies</h2><p>We use essential cookies for the cart and checkout, and optional analytics cookies with your consent.</p><h2>Contact</h2><p>Questions about privacy? Email privacy@acme-fashion.test.</p>',
            ],
            'terms' => [
                'title' => 'Terms of Service',
                'body' => '<h2>Orders and Payments</h2><p>All prices are in EUR and include VAT. Payment is due at checkout.</p><h2>Product Descriptions</h2><p>We describe products as accurately as possible; minor color variance may occur between screens.</p><h2>Limitation of Liability</h2><p>Our liability is limited to the purchase price of the goods.</p><h2>Governing Law</h2><p>These terms are governed by the laws of the Federal Republic of Germany.</p>',
            ],
        ];

        foreach ($pages as $handle => $page) {
            Page::query()->updateOrCreate(
                ['store_id' => $store->getKey(), 'handle' => $handle],
                [
                    'title' => $page['title'],
                    'body_html' => $page['body'],
                    'status' => PageStatus::Published,
                    'published_at' => now()->subMonths(3),
                ]
            );
        }
    }
}
