<?php

namespace App\Services;

use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;

/**
 * Webhook subscription lookup, dispatch, and HMAC signatures
 * (spec 05 §13, spec 06 §7.2).
 */
class WebhookService
{
    /**
     * Queue a delivery job for each active subscription matching the
     * store and event type (spec 05 §13.2).
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        WebhookSubscription::query()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', WebhookSubscriptionStatus::Active)
            ->get()
            ->each(fn (WebhookSubscription $subscription) => DeliverWebhook::dispatch($subscription, $eventType, $payload));
    }

    /**
     * HMAC-SHA256 hex signature over "{timestamp}.{payload}" signed with
     * the subscription's (decrypted) signing secret.
     */
    public function sign(string $payload, string $secret, int $timestamp): string
    {
        return hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
    }

    /**
     * Verify a delivery signature by recomputing it with the given
     * timestamp and comparing in constant time.
     */
    public function verify(string $payload, string $signature, string $secret, int $timestamp): bool
    {
        return hash_equals($this->sign($payload, $secret, $timestamp), $signature);
    }
}
