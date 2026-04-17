<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;

class WebhookService
{
    /**
     * Dispatch webhooks to all matching active subscriptions for a store.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $subscriptions = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            $eventTypes = $subscription->event_types_json ?? [];

            if (in_array($eventType, $eventTypes, true)) {
                DeliverWebhook::dispatch($subscription, $eventType, $payload);
            }
        }
    }

    /**
     * Generate an HMAC-SHA256 signature for a webhook payload.
     */
    public function sign(string $payload, string $secret): string
    {
        $timestamp = time();
        $signaturePayload = "{$timestamp}.{$payload}";

        return hash_hmac('sha256', $signaturePayload, $secret);
    }

    /**
     * Generate a signature with a specific timestamp (for testing).
     */
    public function signWithTimestamp(string $payload, string $secret, int $timestamp): string
    {
        $signaturePayload = "{$timestamp}.{$payload}";

        return hash_hmac('sha256', $signaturePayload, $secret);
    }

    /**
     * Verify an HMAC-SHA256 signature for a webhook payload.
     */
    public function verify(string $payload, string $signature, string $timestamp, string $secret): bool
    {
        // Check timestamp is within 5 minutes
        $timestampInt = (int) $timestamp;
        if (abs(time() - $timestampInt) > 300) {
            return false;
        }

        $signaturePayload = "{$timestamp}.{$payload}";
        $expected = hash_hmac('sha256', $signaturePayload, $secret);

        return hash_equals($expected, $signature);
    }
}
