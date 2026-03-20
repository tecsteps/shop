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
        $fashionStore = Store::where('handle', 'like', '%fashion%')->first();

        if (! $fashionStore) {
            $fashionStore = Store::first();
        }

        if (! $fashionStore) {
            return;
        }

        $publishedAt = now()->subMonths(3);

        $pages = [
            [
                'title' => 'About Us',
                'handle' => 'about',
                'body_html' => '<h2>Our Story</h2><p>Acme Fashion was founded with a simple mission: to provide high-quality, modern essentials that make getting dressed effortless. We believe fashion should be accessible, sustainable, and designed to last.</p><p>From our studio in Berlin, we curate collections that blend timeless style with contemporary design, ensuring every piece in our catalog meets our exacting standards for quality and craftsmanship.</p><h2>Our Values</h2><p>We are committed to ethical sourcing and sustainable practices. Every material we use is carefully selected for its environmental impact. We partner with manufacturers who share our commitment to fair labor practices and responsible production.</p><h2>Our Team</h2><p>Based in Berlin, our team of designers and curators brings together decades of experience in fashion and retail. We are passionate about creating a shopping experience that is as enjoyable as the clothes themselves.</p>',
            ],
            [
                'title' => 'FAQ',
                'handle' => 'faq',
                'body_html' => '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Standard shipping within Germany takes 2-4 business days. Express shipping is available for 1-2 business day delivery. EU orders typically arrive within 5-7 business days.</p><h3>What is your return policy?</h3><p>We accept returns within 30 days of purchase. Items must be unworn, unwashed, and in their original packaging with all tags attached.</p><h3>Do you ship internationally?</h3><p>Yes! We ship to all EU countries as well as the United States, United Kingdom, Canada, and Australia.</p><h3>How can I track my order?</h3><p>Once your order has been shipped, you will receive an email with a tracking number. You can use this number to track your package through our shipping partner\'s website.</p>',
            ],
            [
                'title' => 'Shipping & Returns',
                'handle' => 'shipping-returns',
                'body_html' => '<h3>Shipping Rates</h3><ul><li>Germany Standard (2-4 days): 4.99 EUR</li><li>Germany Express (1-2 days): 9.99 EUR</li><li>EU Standard (5-7 days): 8.99 EUR</li><li>International (7-14 days): 14.99 EUR</li></ul><h3>Returns</h3><p>We offer a 30-day return policy on all items. Items must be in their original, unworn condition with all tags attached. Please note that the customer is responsible for return shipping costs unless the item is defective or we made an error with your order.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'handle' => 'privacy-policy',
                'body_html' => '<h2>Information We Collect</h2><p>We collect information you provide directly to us, such as your name, email address, shipping address, and payment information when you make a purchase.</p><h2>How We Use Your Information</h2><p>We use the information we collect to process transactions, send you order confirmations and updates, and improve our services.</p><h2>Cookies</h2><p>We use cookies and similar technologies to enhance your browsing experience, analyze site traffic, and personalize content.</p><h2>Contact</h2><p>If you have any questions about our privacy practices, please contact us at privacy@acme-fashion.test.</p>',
            ],
            [
                'title' => 'Terms of Service',
                'handle' => 'terms',
                'body_html' => '<h2>Orders and Payments</h2><p>All prices are displayed in EUR and include applicable taxes. We accept major credit cards and bank transfers.</p><h2>Product Descriptions</h2><p>We make every effort to display our products as accurately as possible. However, slight variations in color may occur due to differences in monitor settings.</p><h2>Limitation of Liability</h2><p>Acme Fashion shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of our services.</p><h2>Governing Law</h2><p>These terms shall be governed by the laws of the Federal Republic of Germany.</p>',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::create(array_merge($pageData, [
                'store_id' => $fashionStore->id,
                'status' => PageStatus::Published,
                'published_at' => $publishedAt,
            ]));
        }
    }
}
