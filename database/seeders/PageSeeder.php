<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    use SeedsDemoData;

    /**
     * Seed content pages for the Acme Fashion store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::where('handle', 'acme-fashion')->firstOrFail();

            $pages = [
                [
                    'title' => 'About Us',
                    'handle' => 'about',
                    'body_html' => implode("\n", [
                        '<h2>Our Story</h2>',
                        '<p>Acme Fashion was founded in Berlin with a simple idea: modern essentials should be well made, fairly priced, and kind to the planet. What began as a small market stall now ships across Europe and beyond, but our approach has not changed.</p>',
                        '<p>We design every piece in-house, work closely with a small network of trusted manufacturers, and stand behind the quality of everything we sell.</p>',
                        '<h2>Our Values</h2>',
                        '<p>We are committed to ethical sourcing, sustainable materials, and fair labour conditions across our entire supply chain. Organic cotton, recycled packaging, and transparent production are non-negotiable for us.</p>',
                        '<h2>Our Team</h2>',
                        '<p>Our Berlin-based team of designers, buyers, and customer care specialists is obsessed with the details — from the first sketch to the final stitch. We would love to hear from you, so do not hesitate to get in touch.</p>',
                    ]),
                ],
                [
                    'title' => 'FAQ',
                    'handle' => 'faq',
                    'body_html' => implode("\n", [
                        '<h2>Frequently Asked Questions</h2>',
                        '<h3>How long does shipping take?</h3>',
                        '<p>Within Germany, standard shipping takes 2-4 business days and express shipping 1-2 business days. Deliveries to other EU countries typically arrive within 5-7 business days.</p>',
                        '<h3>What is your return policy?</h3>',
                        '<p>You may return unworn items in their original packaging within 30 days of delivery for a full refund. Items must be returned with all tags attached.</p>',
                        '<h3>Do you ship internationally?</h3>',
                        '<p>Yes. We ship to all EU countries as well as the US, UK, Canada, and Australia. International delivery times and rates are shown at checkout.</p>',
                        '<h3>How can I track my order?</h3>',
                        '<p>As soon as your order ships you will receive an email containing a tracking number and a link to follow your parcel every step of the way.</p>',
                    ]),
                ],
                [
                    'title' => 'Shipping & Returns',
                    'handle' => 'shipping-returns',
                    'body_html' => implode("\n", [
                        '<h2>Shipping Rates</h2>',
                        '<p>All prices are in EUR and include VAT where applicable.</p>',
                        '<h3>Germany</h3>',
                        '<ul>',
                        '<li>Standard shipping: 4.99 EUR (2-4 business days)</li>',
                        '<li>Express shipping: 9.99 EUR (1-2 business days)</li>',
                        '</ul>',
                        '<h3>European Union</h3>',
                        '<ul>',
                        '<li>Standard shipping: 8.99 EUR (5-7 business days)</li>',
                        '</ul>',
                        '<h3>Rest of World</h3>',
                        '<ul>',
                        '<li>International shipping: 14.99 EUR (7-14 business days)</li>',
                        '</ul>',
                        '<h2>Returns</h2>',
                        '<p>You have 30 days from delivery to return unworn items in their original packaging. Unless the item arrived defective or damaged, the customer is responsible for return shipping costs.</p>',
                    ]),
                ],
                [
                    'title' => 'Privacy Policy',
                    'handle' => 'privacy-policy',
                    'body_html' => implode("\n", [
                        '<h2>Information We Collect</h2>',
                        '<p>We collect the information you provide when placing an order or creating an account, including your name, email address, shipping address, and payment details. We also collect limited technical data such as browser type and device information to keep our store secure.</p>',
                        '<h2>How We Use Your Information</h2>',
                        '<p>We use your information to process orders, arrange delivery, provide customer support, and — only with your consent — to send you marketing updates. We never sell your personal data to third parties.</p>',
                        '<h2>Cookies</h2>',
                        '<p>Our store uses cookies to keep your cart working, remember your preferences, and understand how visitors use the site. You can control cookies through your browser settings at any time.</p>',
                        '<h2>Contact</h2>',
                        '<p>For any privacy-related questions, contact us at privacy@acme-fashion.test.</p>',
                    ]),
                ],
                [
                    'title' => 'Terms of Service',
                    'handle' => 'terms',
                    'body_html' => implode("\n", [
                        '<h2>Orders and Payments</h2>',
                        '<p>All prices are listed in EUR and are tax-inclusive. By placing an order you agree to pay the total shown at checkout. We reserve the right to refuse or cancel any order, for example where pricing errors have occurred.</p>',
                        '<h2>Product Descriptions</h2>',
                        '<p>We work hard to display colours and materials accurately, but slight variations can occur between screens. Product measurements are approximate unless stated otherwise.</p>',
                        '<h2>Limitation of Liability</h2>',
                        '<p>To the extent permitted by law, Acme Fashion shall not be liable for indirect or consequential damages arising from the use of this store or its products.</p>',
                        '<h2>Governing Law</h2>',
                        '<p>These terms are governed by the laws of the Federal Republic of Germany. Any disputes shall be subject to the exclusive jurisdiction of the German courts.</p>',
                    ]),
                ],
            ];

            foreach ($pages as $page) {
                Page::updateOrCreate(
                    ['store_id' => $store->id, 'handle' => $page['handle']],
                    [
                        'title' => $page['title'],
                        'body_html' => $page['body_html'],
                        'status' => 'published',
                        'published_at' => $this->resolveDate('3 months ago'),
                    ],
                );
            }
        });
    }
}
