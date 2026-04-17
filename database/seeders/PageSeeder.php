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
        $publishedAt = now()->subMonths(3);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'About Us',
            'handle' => 'about',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Our Story</h2><p>Acme Fashion was founded with a simple mission: to bring modern, high-quality essentials to everyone. We believe that great style should be accessible, sustainable, and timeless.</p><p>Our philosophy centres on creating pieces that work for your life, not just for a single season. Every item in our collection is carefully curated to offer lasting value.</p><h2>Our Values</h2><p>We are committed to ethical sourcing, sustainability, and fair labour practices. Our materials are selected for quality and environmental responsibility, and we partner only with factories that share our values.</p><h2>Our Team</h2><p>Based in Berlin, our team of designers and curators work tirelessly to bring you the best in contemporary fashion. We draw inspiration from the vibrant culture and creativity of our city.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'FAQ',
            'handle' => 'faq',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Standard shipping within Germany takes 2-4 business days. Express shipping delivers within 1-2 business days. EU orders are delivered in 5-7 business days.</p><h3>What is your return policy?</h3><p>We accept returns within 30 days of purchase. Items must be unworn and in their original packaging. Please contact our support team to initiate a return.</p><h3>Do you ship internationally?</h3><p>Yes, we ship to the EU as well as the US, UK, Canada, and Australia. International shipping rates and delivery times vary by destination.</p><h3>How can I track my order?</h3><p>Once your order has been shipped, you will receive an email with your tracking number. You can use this number to track your package on the carrier website.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'Shipping & Returns',
            'handle' => 'shipping-returns',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Shipping</h2><h3>Shipping Rates</h3><ul><li>Germany Standard: 4.99 EUR (2-4 business days)</li><li>Germany Express: 9.99 EUR (1-2 business days)</li><li>EU Standard: 8.99 EUR (5-7 business days)</li><li>International: 14.99 EUR (7-14 business days)</li></ul><h3>Free Shipping</h3><p>We offer free shipping on all orders over 50 EUR within Germany. Use code FREESHIP at checkout.</p><h2>Returns</h2><p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, and in their original packaging with all tags attached. Customers are responsible for return shipping costs unless the item is defective.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'Privacy Policy',
            'handle' => 'privacy-policy',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Privacy Policy</h2><h3>Information We Collect</h3><p>We collect personal information that you provide when placing an order, creating an account, or subscribing to our newsletter. This includes your name, email address, shipping address, and payment information.</p><h3>How We Use Your Information</h3><p>Your information is used to process orders, communicate with you about your purchases, and improve our services. We never sell your personal data to third parties.</p><h3>Cookies</h3><p>Our website uses cookies to enhance your browsing experience and provide analytics. You can manage your cookie preferences in your browser settings.</p><h3>Contact</h3><p>For privacy-related inquiries, please contact us at privacy@acme-fashion.test.</p>',
        ]);

        Page::factory()->published()->create([
            'store_id' => $fashion->id,
            'title' => 'Terms of Service',
            'handle' => 'terms',
            'published_at' => $publishedAt,
            'body_html' => '<h2>Terms of Service</h2><h3>Orders and Payments</h3><p>All prices are listed in EUR and include applicable taxes. We accept credit cards, PayPal, and bank transfers. Orders are confirmed upon successful payment processing.</p><h3>Product Descriptions</h3><p>We make every effort to display product colours and details accurately. However, we cannot guarantee that your display will accurately reflect the actual colour of products. Slight colour variations may occur.</p><h3>Limitation of Liability</h3><p>Acme Fashion shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or services.</p><h3>Governing Law</h3><p>These terms are governed by the laws of the Federal Republic of Germany. Any disputes shall be resolved in the courts of Berlin.</p>',
        ]);
    }
}
