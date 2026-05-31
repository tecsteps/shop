<?php

namespace App\Services;

use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

/**
 * Outbound webhook fan-out and HMAC signing.
 *
 * `dispatch()` finds every active subscription for an event type within a store
 * and queues a {@see DeliverWebhook} job per subscription, each with its own
 * pending {@see WebhookDelivery} record. `sign()`/`verify()` implement the
 * HMAC-SHA256 scheme used in the `X-Platform-Signature` header.
 */
class WebhookService
{
    /**
     * Signature header prefix; the digest follows as `sha256=<hex>`.
     */
    public const SIGNATURE_PREFIX = 'sha256=';

    /**
     * Queue a delivery job for every active subscription in the store that
     * matches the given event type. Returns the number of deliveries queued.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): int
    {
        $subscriptions = WebhookSubscription::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', WebhookSubscriptionStatus::Active->value)
            ->get();

        $queued = 0;

        foreach ($subscriptions as $subscription) {
            $eventId = (string) Str::uuid();

            $delivery = WebhookDelivery::create([
                'subscription_id' => $subscription->id,
                'event_id' => $eventId,
                'attempt_count' => 0,
                'status' => 'pending',
            ]);

            DeliverWebhook::dispatch($delivery->id, $eventType, $payload);

            $queued++;
        }

        return $queued;
    }

    /**
     * Compute the HMAC-SHA256 signature for a raw payload string, prefixed with
     * the scheme identifier (`sha256=`).
     */
    public function sign(string $payload, string $secret): string
    {
        return self::SIGNATURE_PREFIX.hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Constant-time verification of a signature against a payload and secret.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }
}
