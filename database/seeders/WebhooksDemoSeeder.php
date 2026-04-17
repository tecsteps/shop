<?php

namespace Database\Seeders;

use App\Enums\WebhookTopic;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Seeder;

class WebhooksDemoSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'shop')->first();

        if ($store === null) {
            return;
        }

        $subscriptions = [
            ['event' => WebhookTopic::OrderPaid->value, 'url' => 'https://example.com/webhooks/order-paid'],
            ['event' => WebhookTopic::OrderCancelled->value, 'url' => 'https://example.com/webhooks/order-cancelled'],
        ];

        foreach ($subscriptions as $entry) {
            WebhookSubscription::query()
                ->withoutGlobalScopes()
                ->firstOrCreate(
                    [
                        'store_id' => $store->getKey(),
                        'event_type' => $entry['event'],
                        'target_url' => $entry['url'],
                    ],
                    [
                        'signing_secret_encrypted' => 'whsec_demo_'.bin2hex(random_bytes(16)),
                        'status' => 'active',
                        'consecutive_failures' => 0,
                        'created_at' => now(),
                    ],
                );
        }
    }
}
