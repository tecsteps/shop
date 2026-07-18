<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Schema;

class WebhookService
{
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        if (! Schema::hasTable('webhook_subscriptions')) {
            return;
        }

        WebhookSubscription::query()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->each(fn (WebhookSubscription $subscription) => DeliverWebhook::dispatch($subscription->id, $eventType, $payload));
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
