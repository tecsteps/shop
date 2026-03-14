<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;

class WebhookService
{
    /**
     * Dispatch webhook event to all matching active subscriptions for a store.
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $subscriptions = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->get();

        foreach ($subscriptions as $subscription) {
            $delivery = WebhookDelivery::create([
                'subscription_id' => $subscription->id,
                'event_type' => $eventType,
                'payload_json' => $payload,
                'status' => 'pending',
            ]);

            DeliverWebhook::dispatch($delivery);
        }
    }

    /**
     * Generate HMAC-SHA256 signature for a payload.
     */
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify HMAC-SHA256 signature for a payload.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        $expected = $this->sign($payload, $secret);

        return hash_equals($expected, $signature);
    }
}
