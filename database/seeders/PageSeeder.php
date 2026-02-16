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

        $pages = [
            [
                'title' => 'About Us',
                'handle' => 'about',
                'content' => '<h2>Our Story</h2><p>Acme Fashion was founded with a simple mission: to bring you quality, sustainable fashion at fair prices. We believe that looking good should never come at the expense of the planet.</p><h2>Our Values</h2><p>We are committed to ethical sourcing, sustainable production methods, and fair labor practices across our entire supply chain.</p><h2>Our Team</h2><p>Based in Berlin, our team of designers and fashion enthusiasts work tirelessly to curate collections that blend timeless style with modern sensibility.</p>',
            ],
            [
                'title' => 'FAQ',
                'handle' => 'faq',
                'content' => '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Standard shipping within Germany takes 2-4 business days. Express shipping delivers within 1-2 business days. EU orders typically arrive within 5-7 business days.</p><h3>What is your return policy?</h3><p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, and in their original packaging.</p><h3>Do you ship internationally?</h3><p>Yes! We ship to all EU countries as well as the US, UK, Canada, and Australia.</p><h3>How can I track my order?</h3><p>Once your order has been shipped, you will receive an email with a tracking number that you can use to follow your delivery.</p>',
            ],
            [
                'title' => 'Shipping & Returns',
                'handle' => 'shipping-returns',
                'content' => '<h2>Shipping Rates</h2><h3>Germany</h3><ul><li>Standard Shipping: 4.99 EUR (2-4 business days)</li><li>Express Shipping: 9.99 EUR (1-2 business days)</li></ul><h3>EU</h3><ul><li>EU Standard: 8.99 EUR (5-7 business days)</li></ul><h3>International</h3><ul><li>International: 14.99 EUR (7-14 business days)</li></ul><h2>Returns</h2><p>We accept returns within 30 days of delivery. Items must be unworn and in original packaging. Customer pays return shipping unless the item is defective.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'handle' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2><h3>Information We Collect</h3><p>We collect information you provide directly to us, including name, email address, shipping address, and payment information when you make a purchase.</p><h3>How We Use Your Information</h3><p>We use the information we collect to process orders, communicate with you, and improve our services.</p><h3>Cookies</h3><p>We use cookies to enhance your browsing experience and analyze site traffic.</p><h3>Contact</h3><p>For privacy-related questions, contact us at privacy@acme-fashion.test.</p>',
            ],
            [
                'title' => 'Terms of Service',
                'handle' => 'terms',
                'content' => '<h2>Terms of Service</h2><h3>Orders and Payments</h3><p>All prices are in EUR and include applicable taxes. We accept credit cards, PayPal, and bank transfers.</p><h3>Product Descriptions</h3><p>We make every effort to display colors and details accurately, but slight variations may occur due to monitor differences.</p><h3>Limitation of Liability</h3><p>Acme Fashion shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or services.</p><h3>Governing Law</h3><p>These terms shall be governed by and construed in accordance with the laws of the Federal Republic of Germany.</p>',
            ],
        ];

        foreach ($pages as $data) {
            Page::firstOrCreate(
                ['store_id' => $store->id, 'handle' => $data['handle']],
                [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'status' => PageStatus::Published,
                    'published_at' => now()->subMonths(3),
                ]
            );
        }
    }
}
