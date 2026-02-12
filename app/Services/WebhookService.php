<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;

class WebhookService
{
    /**
     * Dispatch webhook deliveries for all active subscriptions matching the store and event type.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $subscriptions = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('topic', $eventType)
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            $delivery = WebhookDelivery::create([
                'webhook_subscription_id' => $subscription->id,
                'event_type' => $eventType,
                'payload_json' => json_encode($payload),
                'created_at' => now(),
            ]);

            $subscription->update(['last_triggered_at' => now()]);

            DeliverWebhook::dispatch($delivery);
        }
    }

    /**
     * Generate an HMAC-SHA256 signature for the given payload.
     */
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify an incoming webhook signature against the expected HMAC.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        $expected = $this->sign($payload, $secret);

        return hash_equals($expected, $signature);
    }
}
