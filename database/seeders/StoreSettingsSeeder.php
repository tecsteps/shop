<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->each(function (Store $store): void {
            StoreSettings::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                [
                    'settings_json' => [
                        'announcement' => [
                            'enabled' => true,
                            'text' => 'Free shipping on orders over 75.00 EUR',
                        ],
                        'checkout' => [
                            'guest_checkout_enabled' => true,
                            'customer_accounts_required' => false,
                            'phone_number_required' => false,
                            'billing_address_enabled' => true,
                            'order_notes_enabled' => true,
                            'terms_required' => false,
                            'terms_url' => '',
                            'payment_hold_hours' => 24,
                            'abandoned_checkout_days' => 14,
                        ],
                        'bank_transfer_cancel_days' => 7,
                        'notifications' => [
                            'sender_name' => $store->name,
                            'sender_email' => 'no-reply@shop.test',
                            'reply_to_email' => '',
                            'order_confirmation_enabled' => true,
                            'shipping_confirmation_enabled' => true,
                            'refund_confirmation_enabled' => true,
                            'admin_order_alerts_enabled' => true,
                            'low_stock_alerts_enabled' => true,
                            'low_stock_threshold' => 5,
                        ],
                    ],
                ],
            );
        });
    }
}
