<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreSettings>
 */
class StoreSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'settings_json' => [
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
                    'sender_name' => 'Acme Store',
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
        ];
    }
}
