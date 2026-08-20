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
            ->where('event', $eventType)
            ->where('status', 'active')
            ->get()
            ->each(function (WebhookSubscription $subscription) use ($eventType, $payload): void {
                $delivery = $subscription->deliveries()->create(['event' => $eventType, 'payload' => $payload, 'attempts' => 0, 'next_attempt_at' => now()]);
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
