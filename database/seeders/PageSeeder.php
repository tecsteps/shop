<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $publishedAt = now()->subMonths(3);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'About Us',
            'handle' => 'about',
            'published_at' => $publishedAt,
            'body_html' => <<<'HTML'
<h2>Our Story</h2>
<p>Acme Fashion was founded in Berlin with a simple mission: to create timeless, high-quality clothing that feels as good as it looks. We believe fashion should be accessible, sustainable, and designed to last.</p>
<p>From our first collection of organic cotton basics, we have grown into a brand trusted by thousands of customers across Europe. Every piece in our collection is thoughtfully designed and responsibly made.</p>

<h2>Our Values</h2>
<p>We are committed to ethical sourcing and sustainable production. Our fabrics come from certified suppliers, and we work exclusively with factories that uphold fair labor standards. We continuously invest in reducing our environmental footprint through responsible packaging, carbon-neutral shipping, and circular fashion initiatives.</p>

<h2>Our Team</h2>
<p>Based in the heart of Berlin, our team of designers, pattern makers, and product specialists brings together decades of experience in the fashion industry. We are passionate about creating clothing that our customers love to wear every day.</p>
HTML,
        ]);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'FAQ',
            'handle' => 'faq',
            'published_at' => $publishedAt,
            'body_html' => <<<'HTML'
<h2>Frequently Asked Questions</h2>

<h3>How long does shipping take?</h3>
<p>Standard shipping within Germany takes 2-4 business days. Express shipping arrives within 1-2 business days. For EU destinations, please allow 5-7 business days for standard delivery.</p>

<h3>What is your return policy?</h3>
<p>We offer a 30-day return policy on all items. Products must be unworn, unwashed, and in their original packaging with all tags attached. Simply contact our support team to initiate a return.</p>

<h3>Do you ship internationally?</h3>
<p>Yes! We ship to all EU countries as well as the United States, United Kingdom, Canada, and Australia. International shipping rates vary by destination.</p>

<h3>How can I track my order?</h3>
<p>Once your order has been shipped, you will receive an email with a tracking number and a link to follow your package in real time.</p>
HTML,
        ]);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'Shipping & Returns',
            'handle' => 'shipping-returns',
            'published_at' => $publishedAt,
            'body_html' => <<<'HTML'
<h2>Shipping Information</h2>

<h3>Shipping Rates</h3>
<ul>
<li><strong>Germany Standard:</strong> 4.99 EUR (2-4 business days)</li>
<li><strong>Germany Express:</strong> 9.99 EUR (1-2 business days)</li>
<li><strong>EU Standard:</strong> 8.99 EUR (5-7 business days)</li>
<li><strong>International:</strong> 14.99 EUR (7-14 business days)</li>
</ul>

<h3>Returns Policy</h3>
<p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, and in their original packaging. To start a return, please contact our customer service team. Return shipping costs are the responsibility of the customer unless the item is defective or we made an error with your order.</p>
HTML,
        ]);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'Privacy Policy',
            'handle' => 'privacy-policy',
            'published_at' => $publishedAt,
            'body_html' => <<<'HTML'
<h2>Privacy Policy</h2>

<h3>Information We Collect</h3>
<p>We collect personal information that you provide when creating an account, placing an order, or contacting us. This includes your name, email address, shipping address, and payment information.</p>

<h3>How We Use Your Information</h3>
<p>Your information is used to process orders, communicate order updates, improve our products and services, and send marketing communications if you have opted in.</p>

<h3>Cookies</h3>
<p>Our website uses cookies to enhance your browsing experience, remember your preferences, and analyze site traffic. You can manage cookie preferences through your browser settings.</p>

<h3>Contact</h3>
<p>For any privacy-related questions, please contact us at privacy@acme-fashion.test.</p>
HTML,
        ]);

        Page::factory()->published()->create([
            'store_id' => $store->id,
            'title' => 'Terms of Service',
            'handle' => 'terms',
            'published_at' => $publishedAt,
            'body_html' => <<<'HTML'
<h2>Terms of Service</h2>

<h3>Orders and Payments</h3>
<p>All prices are displayed in EUR and include applicable taxes. We accept credit cards, PayPal, and bank transfers. Orders are confirmed upon receipt of payment.</p>

<h3>Product Descriptions</h3>
<p>We make every effort to display colors and details accurately. However, slight variations in color may occur due to monitor settings and lighting conditions during photography.</p>

<h3>Limitation of Liability</h3>
<p>Acme Fashion shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or services beyond the purchase price of the item in question.</p>

<h3>Governing Law</h3>
<p>These terms shall be governed by and construed in accordance with the laws of the Federal Republic of Germany. Any disputes shall be resolved in the courts of Berlin, Germany.</p>
HTML,
        ]);
    }
}
