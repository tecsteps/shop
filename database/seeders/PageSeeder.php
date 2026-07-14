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
        foreach ([
            ['About Us', 'about', '<h2>Our Story</h2><p>Acme Fashion creates modern essentials with purpose from our Berlin studio.</p><h2>Our Values</h2><p>Ethical sourcing, sustainability and fair labour guide every decision.</p><h2>Our Team</h2><p>Our Berlin-based designers build lasting wardrobes, not passing trends.</p>'],
            ['FAQ', 'faq', '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Germany orders arrive in 2-4 days, express in 1-2 days, and EU orders in 5-7 days.</p><h3>What is your return policy?</h3><p>Return unworn items in original packaging within 30 days.</p><h3>Do you ship internationally?</h3><p>We ship across the EU and to the US, UK, Canada and Australia.</p><h3>How do I track an order?</h3><p>We email your tracking number as soon as it ships.</p>'],
            ['Shipping & Returns', 'shipping-returns', '<h2>Shipping & Returns</h2><h3>Shipping rates</h3><ul><li>Germany standard: 4.99 EUR</li><li>Germany express: 9.99 EUR</li><li>EU: 8.99 EUR</li><li>International: 14.99 EUR</li></ul><p>Returns are accepted for 30 days. Customers pay return postage unless an item is defective.</p>'],
            ['Privacy Policy', 'privacy-policy', '<h2>Information We Collect</h2><p>We collect the details needed to fulfil your order.</p><h2>How We Use Your Information</h2><p>We use data only to provide and improve our service.</p><h3>Cookies</h3><p>Essential cookies keep your cart and account secure.</p><h3>Contact</h3><p>privacy@acme-fashion.test</p>'],
            ['Terms of Service', 'terms', '<h2>Orders and Payments</h2><p>Orders are charged in EUR and prices include tax.</p><h2>Product Descriptions</h2><p>Screen colours may vary slightly from physical items.</p><h2>Limitation of Liability</h2><p>Liability is limited as permitted by law.</p><h2>Governing Law</h2><p>The laws of the Federal Republic of Germany apply.</p>'],
        ] as [$title, $handle, $body]) {
            Page::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'handle' => $handle], [
                'title' => $title, 'body_html' => $body, 'status' => 'published', 'published_at' => now()->subMonths(3),
            ]);
        }
    }
}
