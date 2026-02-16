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
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->get()
            ->each(function (WebhookSubscription $subscription) use ($eventType, $payload): void {
                $eventTypes = $subscription->event_types_json ?? [];
                if (in_array($eventType, $eventTypes) || in_array('*', $eventTypes)) {
                    DeliverWebhook::dispatch($subscription, $eventType, $payload);
                }
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
