<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

class WebhookService
{
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $subscriptions = WebhookSubscription::query()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->get(['id']);

        if ($subscriptions->isEmpty()) {
            return;
        }

        $eventId = (string) Str::uuid();
        $timestamp = now()->timestamp;

        foreach ($subscriptions as $subscription) {
            DeliverWebhook::dispatch(
                subscriptionId: $subscription->id,
                eventType: $eventType,
                payload: $payload,
                eventId: $eventId,
                timestamp: $timestamp,
            );
        }
    }

    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }

    public function pauseIfConsecutiveFailures(WebhookSubscription $subscription, int $threshold = 5): void
    {
        $recent = WebhookDelivery::query()
            ->where('subscription_id', $subscription->id)
            ->orderByDesc('id')
            ->limit($threshold)
            ->pluck('status');

        if ($recent->count() !== $threshold) {
            return;
        }

        if (! $recent->every(static fn (string $status): bool => $status === 'failed')) {
            return;
        }

        if ($subscription->status === 'paused') {
            return;
        }

        $subscription->status = 'paused';
        $subscription->save();
    }
}
