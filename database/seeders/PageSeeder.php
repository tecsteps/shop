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

        $threeMonthsAgo = now()->subMonths(3);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'About Us',
            'handle' => 'about',
            'body_html' => '<h2>Our Story</h2><p>Acme Fashion was founded with a simple mission: to create timeless, high-quality clothing that empowers people to express their personal style. From our humble beginnings in a small Berlin studio, we have grown into a brand trusted by customers across Europe.</p><p>Our philosophy centers on thoughtful design, quality craftsmanship, and a commitment to making fashion accessible without compromising on standards.</p><h2>Our Values</h2><p>We believe in ethical sourcing, sustainable materials, and fair labor practices throughout our supply chain. Every piece in our collection reflects our dedication to reducing environmental impact while delivering exceptional quality.</p><h2>Our Team</h2><p>Based in the heart of Berlin, our team of designers, artisans, and fashion enthusiasts work together to bring fresh perspectives to modern essentials. We draw inspiration from the city and its creative spirit.</p>',
            'status' => PageStatus::Published,
            'published_at' => $threeMonthsAgo,
        ]);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'FAQ',
            'handle' => 'faq',
            'body_html' => '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Standard shipping within Germany takes 2-4 business days. Express shipping arrives in 1-2 business days. EU orders typically arrive within 5-7 business days.</p><h3>What is your return policy?</h3><p>We accept returns within 30 days of delivery. Items must be unworn and in their original packaging. Please contact our support team to initiate a return.</p><h3>Do you ship internationally?</h3><p>Yes, we ship to the EU as well as the US, UK, Canada, and Australia. International shipping rates vary by destination.</p><h3>How can I track my order?</h3><p>Once your order ships, you will receive an email with a tracking number. You can use this number to track your package on the carrier website.</p>',
            'status' => PageStatus::Published,
            'published_at' => $threeMonthsAgo,
        ]);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'Shipping & Returns',
            'handle' => 'shipping-returns',
            'body_html' => '<h2>Shipping Rates</h2><h3>Germany</h3><ul><li>Standard Shipping: 4.99 EUR (2-4 business days)</li><li>Express Shipping: 9.99 EUR (1-2 business days)</li></ul><h3>European Union</h3><ul><li>EU Standard: 8.99 EUR (5-7 business days)</li></ul><h3>International</h3><ul><li>Rest of World: 14.99 EUR (7-14 business days)</li></ul><h2>Returns Policy</h2><p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, and in their original packaging with all tags attached. The customer is responsible for return shipping costs unless the item is defective or we made an error with your order.</p>',
            'status' => PageStatus::Published,
            'published_at' => $threeMonthsAgo,
        ]);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'Privacy Policy',
            'handle' => 'privacy-policy',
            'body_html' => '<h2>Privacy Policy</h2><h3>Information We Collect</h3><p>We collect information you provide directly, such as your name, email address, shipping address, and payment details when you make a purchase or create an account.</p><h3>How We Use Your Information</h3><p>Your information is used to process orders, communicate with you about your purchases, and improve our services. We never sell your personal data to third parties.</p><h3>Cookies</h3><p>Our website uses cookies to enhance your browsing experience, remember your preferences, and analyze site traffic. You can manage cookie preferences in your browser settings.</p><h3>Contact</h3><p>For privacy-related inquiries, please contact us at privacy@acme-fashion.test.</p>',
            'status' => PageStatus::Published,
            'published_at' => $threeMonthsAgo,
        ]);

        Page::factory()->create([
            'store_id' => $store->id,
            'title' => 'Terms of Service',
            'handle' => 'terms',
            'body_html' => '<h2>Terms of Service</h2><h3>Orders and Payments</h3><p>All prices are displayed in EUR and include applicable taxes. We accept credit cards, PayPal, and bank transfers as payment methods.</p><h3>Product Descriptions</h3><p>We strive to display products as accurately as possible. However, slight color variations may occur due to differences in display settings.</p><h3>Limitation of Liability</h3><p>Acme Fashion shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or services.</p><h3>Governing Law</h3><p>These terms are governed by the laws of the Federal Republic of Germany. Any disputes shall be resolved in the courts of Berlin, Germany.</p>',
            'status' => PageStatus::Published,
            'published_at' => $threeMonthsAgo,
        ]);
    }
}
