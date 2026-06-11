<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seed the five published content pages for the Acme Fashion demo store.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        foreach ($this->pages() as $page) {
            Page::query()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'handle' => $page['handle'],
                ],
                [
                    'title' => $page['title'],
                    'body_html' => $page['body_html'],
                    'status' => PageStatus::Published,
                    'published_at' => now()->subMonths(3),
                ],
            );
        }
    }

    /**
     * @return list<array{title: string, handle: string, body_html: string}>
     */
    private function pages(): array
    {
        return [
            [
                'title' => 'About Us',
                'handle' => 'about',
                'body_html' => <<<'HTML'
                    <h2>Our Story</h2>
                    <p>Acme Fashion was founded with a simple mission: to make modern, well-crafted clothing accessible to everyone. What began as a small studio project has grown into a curated label trusted by customers across Europe.</p>
                    <p>We believe great style should never come at the cost of quality. Every piece in our collection is designed to last, season after season, with timeless silhouettes and dependable materials.</p>
                    <h2>Our Values</h2>
                    <p>We source our fabrics from ethical suppliers, prioritize sustainable production methods, and partner only with factories that guarantee fair labor conditions. Transparency is at the heart of everything we make.</p>
                    <p>From recycled packaging to carbon-conscious shipping, we are constantly working to reduce our footprint while raising the bar for responsible fashion.</p>
                    <h2>Our Team</h2>
                    <p>Our Berlin-based design team blends classic tailoring with contemporary streetwear influences. Together with our buyers and customer care crew, they make sure every Acme Fashion experience feels personal.</p>
                    HTML,
            ],
            [
                'title' => 'FAQ',
                'handle' => 'faq',
                'body_html' => <<<'HTML'
                    <h2>Frequently Asked Questions</h2>
                    <h3>How long does shipping take?</h3>
                    <p>Orders within Germany arrive in 2-4 business days with standard shipping, or 1-2 business days with express shipping. Deliveries to the rest of the EU typically take 5-7 business days.</p>
                    <h3>What is your return policy?</h3>
                    <p>You can return any item within 30 days of delivery, as long as it is unworn and in its original packaging. Start a return from your account page or contact our support team.</p>
                    <h3>Do you ship internationally?</h3>
                    <p>Yes. In addition to the EU, we currently ship to the United States, the United Kingdom, Canada, and Australia.</p>
                    <h3>How can I track my order?</h3>
                    <p>As soon as your order ships, you will receive an email with a tracking number so you can follow your parcel every step of the way.</p>
                    HTML,
            ],
            [
                'title' => 'Shipping & Returns',
                'handle' => 'shipping-returns',
                'body_html' => <<<'HTML'
                    <h2>Shipping Rates</h2>
                    <h3>Germany</h3>
                    <ul>
                        <li>Standard shipping (2-4 business days): 4.99 EUR</li>
                        <li>Express shipping (1-2 business days): 9.99 EUR</li>
                    </ul>
                    <h3>European Union</h3>
                    <ul>
                        <li>Standard shipping (5-7 business days): 8.99 EUR</li>
                    </ul>
                    <h3>International</h3>
                    <ul>
                        <li>Standard shipping (7-14 business days): 14.99 EUR</li>
                    </ul>
                    <h2>Returns</h2>
                    <p>We accept returns within 30 days of delivery. Items must be unworn and returned in their original packaging. Return shipping costs are paid by the customer unless the item arrived damaged or defective, in which case we cover all costs and offer a full refund or replacement.</p>
                    HTML,
            ],
            [
                'title' => 'Privacy Policy',
                'handle' => 'privacy-policy',
                'body_html' => <<<'HTML'
                    <h2>Privacy Policy</h2>
                    <h3>Information We Collect</h3>
                    <p>We collect the information you provide when creating an account, placing an order, or contacting support. This includes your name, email address, shipping address, and order history. Payment details are processed securely and never stored on our servers.</p>
                    <h3>How We Use Your Information</h3>
                    <p>Your data is used to fulfill orders, provide customer support, and, with your consent, send you updates about new products and offers. We never sell your personal information to third parties.</p>
                    <h3>Cookies</h3>
                    <p>We use cookies to keep your cart between visits, remember your preferences, and understand how our store is used so we can improve it. You can disable cookies in your browser settings at any time.</p>
                    <h3>Contact</h3>
                    <p>For any privacy-related questions or requests, please contact us at privacy@acme-fashion.test.</p>
                    HTML,
            ],
            [
                'title' => 'Terms of Service',
                'handle' => 'terms',
                'body_html' => <<<'HTML'
                    <h2>Terms of Service</h2>
                    <h3>Orders and Payments</h3>
                    <p>All prices are listed in EUR and include applicable taxes. An order is confirmed once payment has been authorized. We reserve the right to cancel orders in cases of suspected fraud or pricing errors.</p>
                    <h3>Product Descriptions</h3>
                    <p>We strive to present our products as accurately as possible. Please note that colors may vary slightly depending on your screen settings, and minor variations are not considered defects.</p>
                    <h3>Limitation of Liability</h3>
                    <p>Acme Fashion is not liable for indirect or consequential damages arising from the use of our products or website, to the extent permitted by law. Your statutory rights remain unaffected.</p>
                    <h3>Governing Law</h3>
                    <p>These terms are governed by the laws of the Federal Republic of Germany. Place of jurisdiction, where legally permissible, is Berlin.</p>
                    HTML,
            ],
        ];
    }
}
