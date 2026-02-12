<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

        $publishedAt = now()->subMonths(3);

        $pages = [
            [
                'title' => 'About Us',
                'handle' => 'about',
                'status' => 'published',
                'published_at' => $publishedAt,
                'body_html' => $this->aboutUsHtml(),
            ],
            [
                'title' => 'FAQ',
                'handle' => 'faq',
                'status' => 'published',
                'published_at' => $publishedAt,
                'body_html' => $this->faqHtml(),
            ],
            [
                'title' => 'Shipping & Returns',
                'handle' => 'shipping-returns',
                'status' => 'published',
                'published_at' => $publishedAt,
                'body_html' => $this->shippingReturnsHtml(),
            ],
            [
                'title' => 'Privacy Policy',
                'handle' => 'privacy-policy',
                'status' => 'published',
                'published_at' => $publishedAt,
                'body_html' => $this->privacyPolicyHtml(),
            ],
            [
                'title' => 'Terms of Service',
                'handle' => 'terms',
                'status' => 'published',
                'published_at' => $publishedAt,
                'body_html' => $this->termsOfServiceHtml(),
            ],
        ];

        foreach ($pages as $data) {
            Page::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $fashion->id, 'handle' => $data['handle']],
                $data,
            );
        }
    }

    private function aboutUsHtml(): string
    {
        return <<<'HTML'
        <h2>Our Story</h2>
        <p>Acme Fashion was born from a simple belief: everyone deserves access to well-crafted, thoughtfully designed clothing. Founded in Berlin in 2020, we set out to bridge the gap between high fashion and everyday wear.</p>
        <p>Our philosophy centres on timeless design over fleeting trends. Each piece in our collection is carefully curated to mix and match effortlessly, giving you a versatile wardrobe that lasts season after season.</p>

        <h2>Our Values</h2>
        <p>Sustainability is at the heart of everything we do. We source our materials from certified ethical suppliers who share our commitment to fair labour practices and environmental responsibility.</p>
        <p>From organic cotton tees to recycled polyester outerwear, we are constantly exploring new ways to reduce our footprint without compromising on quality or style.</p>

        <h2>Our Team</h2>
        <p>Based in the creative heart of Berlin, our team of designers, pattern makers, and stylists work together to bring you collections that feel both contemporary and enduring.</p>
        <p>We are a small but passionate group united by a love for great clothing and a drive to do things differently in the fashion industry.</p>
        HTML;
    }

    private function faqHtml(): string
    {
        return <<<'HTML'
        <h2>Frequently Asked Questions</h2>

        <h3>How long does shipping take?</h3>
        <p>Standard shipping within Germany takes 2-4 business days. Express shipping is available for 1-2 business day delivery. EU orders typically arrive within 5-7 business days.</p>

        <h3>What is your return policy?</h3>
        <p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, and in their original packaging with all tags attached. Simply contact our support team to initiate a return.</p>

        <h3>Do you ship internationally?</h3>
        <p>Yes, we ship throughout the EU as well as to the United States, United Kingdom, Canada, and Australia. International shipping rates and delivery times vary by destination.</p>

        <h3>How can I track my order?</h3>
        <p>Once your order has been shipped, you will receive an email with your tracking number. You can use this number to follow your package through the carrier's website.</p>
        HTML;
    }

    private function shippingReturnsHtml(): string
    {
        return <<<'HTML'
        <h2>Shipping Information</h2>

        <h3>Shipping Rates</h3>
        <ul>
            <li>Germany Standard (2-4 business days): 4.99 EUR</li>
            <li>Germany Express (1-2 business days): 9.99 EUR</li>
            <li>EU Standard (5-7 business days): 8.99 EUR</li>
            <li>International (7-14 business days): 14.99 EUR</li>
        </ul>
        <p>Orders over 50 EUR qualify for free standard shipping within Germany.</p>

        <h3>Returns</h3>
        <p>We want you to love your purchase. If something does not work out, you have 30 days from delivery to return it for a full refund. Items must be in their original condition with all tags attached.</p>
        <p>The customer is responsible for return shipping costs unless the item arrived defective or incorrect. In those cases, we will provide a prepaid return label.</p>
        HTML;
    }

    private function privacyPolicyHtml(): string
    {
        return <<<'HTML'
        <h2>Privacy Policy</h2>

        <h3>Information We Collect</h3>
        <p>We collect information you provide directly, such as your name, email address, shipping address, and payment details when you place an order. We also collect browsing data and device information to improve your shopping experience.</p>

        <h3>How We Use Your Information</h3>
        <p>Your personal information is used to process orders, communicate about your purchases, and improve our services. If you have opted in, we may also send you marketing communications about new products and promotions.</p>

        <h3>Cookies</h3>
        <p>We use cookies and similar technologies to remember your preferences, keep items in your shopping cart, and analyse how our site is used. You can manage your cookie preferences through your browser settings.</p>

        <h3>Contact</h3>
        <p>If you have questions about our privacy practices, please contact us at privacy@acme-fashion.test.</p>
        HTML;
    }

    private function termsOfServiceHtml(): string
    {
        return <<<'HTML'
        <h2>Terms of Service</h2>

        <h3>Orders and Payments</h3>
        <p>All prices are displayed in EUR and include applicable taxes. We accept credit cards, PayPal, and bank transfers. An order is confirmed once payment has been successfully processed.</p>

        <h3>Product Descriptions</h3>
        <p>We make every effort to display product colours and details as accurately as possible. However, slight variations may occur due to monitor settings and photographic lighting. These minor differences do not constitute grounds for a return.</p>

        <h3>Limitation of Liability</h3>
        <p>Acme Fashion shall not be held liable for any indirect, incidental, or consequential damages arising from the use of our products or services beyond the purchase price of the item in question.</p>

        <h3>Governing Law</h3>
        <p>These terms are governed by and construed in accordance with the laws of the Federal Republic of Germany. Any disputes shall be resolved in the courts of Berlin, Germany.</p>
        HTML;
    }
}
