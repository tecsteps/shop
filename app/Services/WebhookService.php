<?php

namespace App\Services;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

/**
 * Outbound webhook dispatch, payload signing, and signature verification
 * (spec 05 section 13).
 */
class WebhookService
{
    /**
     * The supported webhook event types (spec 05 section 13.1).
     *
     * @var list<string>
     */
    public const array EVENT_TYPES = [
        'order.created',
        'order.paid',
        'order.fulfilled',
        'order.cancelled',
        'order.refunded',
        'product.created',
        'product.updated',
        'product.deleted',
        'checkout.completed',
    ];

    /**
     * The outbound payload schema version (spec 02 section 9).
     */
    public const string API_VERSION = 'v1';

    /**
     * Find the store's active subscriptions for the event type, create a
     * pending delivery record for each, and queue a DeliverWebhook job.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $subscriptions = WebhookSubscription::query()
            ->withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->getKey())
            ->where('event_type', $eventType)
            ->where('status', WebhookSubscriptionStatus::Active)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $eventId = (string) Str::uuid();
        $occurredAt = now();

        $envelope = [
            'id' => $eventId,
            'event' => $eventType,
            'api_version' => self::API_VERSION,
            'store_id' => $store->getKey(),
            'created_at' => $occurredAt->toIso8601String(),
            'data' => $payload,
        ];

        foreach ($subscriptions as $subscription) {
            $delivery = WebhookDelivery::query()->create([
                'subscription_id' => $subscription->getKey(),
                'event_id' => $eventId,
                'attempt_count' => 0,
                'status' => WebhookDeliveryStatus::Pending,
            ]);

            DeliverWebhook::dispatch($delivery, $envelope, $occurredAt->getTimestamp());
        }
    }

    /**
     * HMAC-SHA256 hex digest of the raw payload body.
     */
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Timing-safe verification of an incoming webhook signature.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }
}
