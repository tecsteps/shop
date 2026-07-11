<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::query()->where('handle', 'acme-fashion')->sole();
            $pages = [
                ['About Us', 'about', '<h2>Our Story</h2><p>Acme Fashion creates modern essentials designed in Berlin.</p><h2>Our Values</h2><p>We believe in ethical sourcing, sustainability, and fair labor.</p><h2>Our Team</h2><p>Our Berlin-based designers create thoughtful, long-lasting pieces.</p>'],
                ['FAQ', 'faq', '<h2>Frequently Asked Questions</h2><h3>How long does shipping take?</h3><p>Germany standard shipping takes 2-4 days, express 1-2 days, and EU shipping 5-7 days.</p><h3>What is your return policy?</h3><p>Returns are accepted within 30 days for unworn items in original packaging.</p><h3>Do you ship internationally?</h3><p>We ship across the EU and to the US, UK, Canada, and Australia.</p><h3>How can I track my order?</h3><p>We email a tracking number after shipment.</p>'],
                ['Shipping & Returns', 'shipping-returns', '<h2>Shipping & Returns</h2><h3>Shipping rates</h3><ul><li>Germany Standard: 4.99 EUR</li><li>Germany Express: 9.99 EUR</li><li>EU: 8.99 EUR</li><li>International: 14.99 EUR</li></ul><h3>Returns</h3><p>Return unworn products within 30 days. Customers pay return shipping unless an item is defective.</p>'],
                ['Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><h3>Information We Collect</h3><p>We collect information needed to process orders.</p><h3>How We Use Your Information</h3><p>We use your information to provide and improve our services.</p><h3>Cookies</h3><p>Cookies keep the storefront secure and functional.</p><h3>Contact</h3><p>Contact privacy@acme-fashion.test.</p>'],
                ['Terms of Service', 'terms', '<h2>Terms of Service</h2><h3>Orders and Payments</h3><p>Orders are paid in EUR and prices include tax.</p><h3>Product Descriptions</h3><p>Screen settings may cause slight color variance.</p><h3>Limitation of Liability</h3><p>Liability is limited as permitted by law.</p><h3>Governing Law</h3><p>These terms are governed by the laws of the Federal Republic of Germany.</p>'],
            ];
            foreach ($pages as [$title, $handle, $body]) {
                Page::withoutGlobalScopes()->updateOrCreate(
                    ['store_id' => $store->id, 'handle' => $handle],
                    ['title' => $title, 'body_html' => $body, 'status' => 'published', 'published_at' => now()->subMonths(3)],
                );
            }
        });
    }
}
