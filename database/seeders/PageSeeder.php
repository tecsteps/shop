<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    /**
     * Seed content pages for Acme Fashion.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::where('handle', 'acme-fashion')->firstOrFail();

            foreach ($this->pageDefinitions() as $data) {
                Page::withoutGlobalScopes()->firstOrCreate(
                    ['store_id' => $store->id, 'handle' => $data['handle']],
                    [
                        'title' => $data['title'],
                        'body_html' => $data['body_html'],
                        'status' => 'published',
                        'published_at' => now()->subMonths(3),
                    ],
                );
            }
        });
    }

    /**
     * @return array<int, array{title: string, handle: string, body_html: string}>
     */
    private function pageDefinitions(): array
    {
        return [
            [
                'title' => 'About Us',
                'handle' => 'about',
                'body_html' => '<h2>Our Story</h2>'
                    .'<p>Acme Fashion was founded with a simple mission: to make high-quality, '
                    .'sustainably produced clothing accessible to everyone. Based in the heart of Berlin, '
                    .'our team of designers draws inspiration from contemporary street style and timeless '
                    .'European craftsmanship.</p>'
                    .'<h2>Our Values</h2>'
                    .'<p>We believe in ethical sourcing, sustainable production, and fair labor practices. '
                    .'Every piece in our collection is crafted with care, using materials sourced from '
                    .'certified suppliers who share our commitment to the environment.</p>'
                    .'<h2>Our Team</h2>'
                    .'<p>Our Berlin-based team of designers, pattern makers, and textile experts work '
                    .'together to bring you collections that blend comfort with style. We are passionate '
                    .'about creating clothing that looks good and feels even better.</p>',
            ],
            [
                'title' => 'FAQ',
                'handle' => 'faq',
                'body_html' => '<h2>Frequently Asked Questions</h2>'
                    .'<h3>How long does shipping take?</h3>'
                    .'<p>Standard shipping within Germany takes 2-4 business days. Express shipping '
                    .'delivers in 1-2 business days. EU orders typically arrive within 5-7 business days.</p>'
                    .'<h3>What is your return policy?</h3>'
                    .'<p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, '
                    .'and in their original packaging with all tags attached.</p>'
                    .'<h3>Do you ship internationally?</h3>'
                    .'<p>Yes, we ship to all EU countries as well as the United States, United Kingdom, '
                    .'Canada, and Australia. International shipping rates vary by destination.</p>'
                    .'<h3>How can I track my order?</h3>'
                    .'<p>Once your order has been shipped, you will receive an email with your tracking '
                    .'number. You can use this number to track your package on the carrier website.</p>',
            ],
            [
                'title' => 'Shipping & Returns',
                'handle' => 'shipping-returns',
                'body_html' => '<h2>Shipping Information</h2>'
                    .'<h3>Shipping Rates</h3>'
                    .'<ul>'
                    .'<li>Germany Standard: 4.99 EUR (2-4 business days)</li>'
                    .'<li>Germany Express: 9.99 EUR (1-2 business days)</li>'
                    .'<li>EU Standard: 8.99 EUR (5-7 business days)</li>'
                    .'<li>International: 14.99 EUR (7-14 business days)</li>'
                    .'</ul>'
                    .'<h2>Returns Policy</h2>'
                    .'<p>We offer a 30-day return policy on all items. Products must be in their original '
                    .'condition, unworn, and with all tags still attached. Return shipping costs are covered '
                    .'by the customer unless the item is defective or we made an error with your order.</p>'
                    .'<p>To initiate a return, please contact our support team with your order number.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'handle' => 'privacy-policy',
                'body_html' => '<h2>Privacy Policy</h2>'
                    .'<h3>Information We Collect</h3>'
                    .'<p>We collect information you provide when creating an account, placing an order, '
                    .'or subscribing to our newsletter. This includes your name, email address, shipping '
                    .'address, and payment information.</p>'
                    .'<h3>How We Use Your Information</h3>'
                    .'<p>Your information is used to process orders, send shipping notifications, and '
                    .'improve our services. We never sell your personal data to third parties.</p>'
                    .'<h3>Cookies</h3>'
                    .'<p>We use cookies to maintain your shopping session, remember your preferences, '
                    .'and analyze site traffic. You can manage cookie preferences in your browser settings.</p>'
                    .'<h3>Contact</h3>'
                    .'<p>For privacy-related inquiries, please contact us at privacy@acme-fashion.test.</p>',
            ],
            [
                'title' => 'Terms of Service',
                'handle' => 'terms',
                'body_html' => '<h2>Terms of Service</h2>'
                    .'<h3>Orders and Payments</h3>'
                    .'<p>All prices are listed in EUR and include applicable taxes. We accept credit cards, '
                    .'PayPal, and bank transfers. Orders are confirmed once payment is received.</p>'
                    .'<h3>Product Descriptions</h3>'
                    .'<p>We strive to display product colors and details as accurately as possible. However, '
                    .'slight variations in color may occur due to monitor settings and lighting conditions.</p>'
                    .'<h3>Limitation of Liability</h3>'
                    .'<p>Acme Fashion shall not be liable for any indirect, incidental, or consequential '
                    .'damages arising from the use of our products or services.</p>'
                    .'<h3>Governing Law</h3>'
                    .'<p>These terms are governed by the laws of the Federal Republic of Germany. Any '
                    .'disputes shall be resolved in the courts of Berlin, Germany.</p>',
            ],
        ];
    }
}
