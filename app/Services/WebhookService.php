<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;

class WebhookService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        WebhookSubscription::where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->get()
            ->each(fn (WebhookSubscription $subscription) => DeliverWebhook::dispatch($subscription, $eventType, $payload));
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
