<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;

class WebhookService
{
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where(function ($query) use ($eventType): void {
                $query->where('event', $eventType)->orWhere('event_type', $eventType);
            })
            ->where('status', 'active')
            ->get()
            ->each(function (WebhookSubscription $subscription) use ($eventType, $payload): void {
                $delivery = $subscription->deliveries()->create(['event' => $eventType, 'event_id' => (string) str()->uuid(), 'payload' => $payload, 'attempts' => 0, 'attempt_count' => 0, 'next_attempt_at' => now()]);
                DeliverWebhook::dispatch($delivery);
            });
    }

    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }
}
