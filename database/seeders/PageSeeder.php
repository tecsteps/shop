<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $publishedAt = now()->subMonths(3)->toIso8601String();

            $pages = [
                [
                    'title' => 'About Us',
                    'handle' => 'about',
                    'body_html' => $this->aboutUsHtml(),
                ],
                [
                    'title' => 'FAQ',
                    'handle' => 'faq',
                    'body_html' => $this->faqHtml(),
                ],
                [
                    'title' => 'Shipping & Returns',
                    'handle' => 'shipping-returns',
                    'body_html' => $this->shippingReturnsHtml(),
                ],
                [
                    'title' => 'Privacy Policy',
                    'handle' => 'privacy-policy',
                    'body_html' => $this->privacyPolicyHtml(),
                ],
                [
                    'title' => 'Terms of Service',
                    'handle' => 'terms',
                    'body_html' => $this->termsHtml(),
                ],
            ];

            foreach ($pages as $data) {
                Page::firstOrCreate(
                    ['store_id' => $fashion->id, 'handle' => $data['handle']],
                    [
                        'title' => $data['title'],
                        'body_html' => $data['body_html'],
                        'status' => PageStatus::Published,
                        'published_at' => $publishedAt,
                    ],
                );
            }
        });
    }

    private function aboutUsHtml(): string
    {
        return <<<'HTML'
<h2>Our Story</h2>
<p>Acme Fashion was founded with a simple mission: to make quality, modern fashion accessible to everyone. We believe that looking good should not come at the expense of comfort, sustainability, or your wallet.</p>
<p>From our headquarters in Berlin, we design and curate a collection of essential wardrobe pieces that combine timeless style with contemporary design.</p>
<h2>Our Values</h2>
<p>We are committed to ethical sourcing and sustainable production. Every piece in our collection is crafted with care, using materials that are kind to both you and the environment. We work with factories that uphold fair labor standards and prioritize worker well-being.</p>
<h2>Our Team</h2>
<p>Our Berlin-based team of designers, stylists, and fashion enthusiasts works tirelessly to bring you the best in modern essentials. We are passionate about fashion and dedicated to providing an exceptional shopping experience.</p>
HTML;
    }

    private function faqHtml(): string
    {
        return <<<'HTML'
<h2>Frequently Asked Questions</h2>
<h3>How long does shipping take?</h3>
<p>Standard shipping within Germany takes 2-4 business days. Express shipping is available for 1-2 business day delivery. EU orders typically arrive within 5-7 business days.</p>
<h3>What is your return policy?</h3>
<p>We accept returns within 30 days of delivery. Items must be unworn, unwashed, and in their original packaging with all tags attached. To initiate a return, please contact our support team.</p>
<h3>Do you ship internationally?</h3>
<p>Yes, we ship to the EU as well as the US, UK, Canada, and Australia. International shipping rates and delivery times vary by destination.</p>
<h3>How can I track my order?</h3>
<p>Once your order has been shipped, you will receive an email with a tracking number and a link to track your package. You can also check your order status in your account dashboard.</p>
HTML;
    }

    private function shippingReturnsHtml(): string
    {
        return <<<'HTML'
<h2>Shipping</h2>
<h3>Shipping Rates</h3>
<ul>
<li>Germany - Standard: 4.99 EUR (2-4 business days)</li>
<li>Germany - Express: 9.99 EUR (1-2 business days)</li>
<li>EU: 8.99 EUR (5-7 business days)</li>
<li>International (US, UK, CA, AU): 14.99 EUR (7-14 business days)</li>
</ul>
<h2>Returns</h2>
<p>We want you to be completely happy with your purchase. If something does not work out, you can return it within 30 days of delivery for a full refund.</p>
<p>Items must be unworn and in their original condition with all tags attached. The customer is responsible for return shipping costs unless the item is defective or we made an error with your order.</p>
<p>To start a return, please contact us at hello@acme-fashion.test with your order number.</p>
HTML;
    }

    private function privacyPolicyHtml(): string
    {
        return <<<'HTML'
<h2>Privacy Policy</h2>
<h3>Information We Collect</h3>
<p>We collect information you provide directly to us, such as your name, email address, shipping address, and payment information when you make a purchase or create an account.</p>
<h3>How We Use Your Information</h3>
<p>We use the information we collect to process orders, communicate with you about your purchases, and improve our services. If you have opted in, we may also send you marketing communications about new products and promotions.</p>
<h3>Cookies</h3>
<p>We use cookies and similar technologies to provide and improve our services, understand how you use our website, and serve relevant content and advertisements.</p>
<h3>Contact</h3>
<p>If you have any questions about this privacy policy, please contact us at privacy@acme-fashion.test.</p>
HTML;
    }

    private function termsHtml(): string
    {
        return <<<'HTML'
<h2>Terms of Service</h2>
<h3>Orders and Payments</h3>
<p>All prices are displayed in EUR and are inclusive of applicable taxes. We accept credit cards, PayPal, and bank transfers as payment methods.</p>
<h3>Product Descriptions</h3>
<p>We make every effort to display product colors and details as accurately as possible. However, we cannot guarantee that your device display will accurately reflect the actual colors of the products. Slight variations in color may occur.</p>
<h3>Limitation of Liability</h3>
<p>Acme Fashion shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our services or products.</p>
<h3>Governing Law</h3>
<p>These terms shall be governed by and construed in accordance with the laws of the Federal Republic of Germany, without regard to its conflict of law provisions.</p>
HTML;
    }
}
