<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    /**
     * Create the Acme Fashion content pages (spec 07 §3.15).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

            $pages = [
                [
                    'title' => 'About Us',
                    'handle' => 'about',
                    'body_html' => '<h2>Our Story</h2>'
                        .'<p>Acme Fashion was founded in Berlin with a simple mission: to create modern wardrobe essentials that last. We believe great style should not come at the expense of comfort, quality, or the planet.</p>'
                        .'<p>Every piece in our collection is designed in-house and produced in small batches, so we can focus on the details that matter - the fabric, the fit, and the finish.</p>'
                        .'<h2>Our Values</h2>'
                        .'<p>We are committed to ethical sourcing and work only with certified suppliers who share our standards. Our cotton is organic, our wool is mulesing-free, and our packaging is fully recyclable.</p>'
                        .'<p>Sustainability and fair labor are not marketing words for us. We visit our partner factories regularly and publish an annual transparency report.</p>'
                        .'<h2>Our Team</h2>'
                        .'<p>We are a small team of Berlin-based designers, pattern makers, and product people. When you write to us, you talk to the same people who designed the clothes you wear.</p>',
                ],
                [
                    'title' => 'FAQ',
                    'handle' => 'faq',
                    'body_html' => '<h2>Frequently Asked Questions</h2>'
                        .'<h3>How long does shipping take?</h3>'
                        .'<p>Orders within Germany arrive in 2-4 business days with standard shipping and 1-2 business days with express shipping. Deliveries to EU countries take 5-7 business days.</p>'
                        .'<h3>What is your return policy?</h3>'
                        .'<p>You can return any unworn item in its original packaging within 30 days of delivery for a full refund.</p>'
                        .'<h3>Do you ship internationally?</h3>'
                        .'<p>Yes. We ship to all EU countries as well as the United States, United Kingdom, Canada, and Australia.</p>'
                        .'<h3>How can I track my order?</h3>'
                        .'<p>As soon as your order ships, you will receive an email with a tracking number and a link to follow your delivery.</p>',
                ],
                [
                    'title' => 'Shipping & Returns',
                    'handle' => 'shipping-returns',
                    'body_html' => '<h2>Shipping Rates</h2>'
                        .'<h3>Germany</h3>'
                        .'<ul><li>Standard shipping (2-4 business days): 4.99 EUR</li><li>Express shipping (1-2 business days): 9.99 EUR</li></ul>'
                        .'<h3>European Union</h3>'
                        .'<ul><li>EU Standard (5-7 business days): 8.99 EUR</li></ul>'
                        .'<h3>International</h3>'
                        .'<ul><li>US, UK, Canada, Australia: 14.99 EUR</li></ul>'
                        .'<h2>Returns</h2>'
                        .'<p>You may return unworn items in their original packaging within 30 days of delivery. Return shipping is paid by the customer unless the item is defective - in that case we cover all costs and send a prepaid label.</p>',
                ],
                [
                    'title' => 'Privacy Policy',
                    'handle' => 'privacy-policy',
                    'body_html' => '<h2>Privacy Policy</h2>'
                        .'<h3>Information We Collect</h3>'
                        .'<p>We collect the information you provide when creating an account or placing an order: your name, email address, shipping and billing addresses, and order history.</p>'
                        .'<h3>How We Use Your Information</h3>'
                        .'<p>We use your information to process orders, arrange delivery, and - only with your consent - send marketing emails. We never sell your data to third parties.</p>'
                        .'<h3>Cookies</h3>'
                        .'<p>We use strictly necessary cookies to operate the shop and anonymous analytics cookies to understand how the store is used.</p>'
                        .'<h3>Contact</h3>'
                        .'<p>For any privacy questions or data requests, contact us at privacy@acme-fashion.test.</p>',
                ],
                [
                    'title' => 'Terms of Service',
                    'handle' => 'terms',
                    'body_html' => '<h2>Terms of Service</h2>'
                        .'<h3>Orders and Payments</h3>'
                        .'<p>All prices are shown in EUR and include applicable taxes. Payment is processed at the time of ordering via the selected payment method.</p>'
                        .'<h3>Product Descriptions</h3>'
                        .'<p>We make every effort to display colors and details accurately, but slight variance can occur due to screen settings and production batches.</p>'
                        .'<h3>Limitation of Liability</h3>'
                        .'<p>Our liability is limited to the purchase price of the affected products. Nothing in these terms limits your statutory consumer rights.</p>'
                        .'<h3>Governing Law</h3>'
                        .'<p>These terms are governed by the laws of the Federal Republic of Germany.</p>',
                ],
            ];

            foreach ($pages as $attributes) {
                Page::query()->updateOrCreate(
                    ['store_id' => $fashion->id, 'handle' => $attributes['handle']],
                    [
                        'title' => $attributes['title'],
                        'body_html' => $attributes['body_html'],
                        'status' => PageStatus::Published,
                        'published_at' => now()->subMonths(3),
                    ],
                );
            }
        });
    }
}
