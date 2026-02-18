<?php

namespace App\Services;

use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Dispatch webhook events to all matching subscriptions for the store.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $subscriptions = WebhookSubscription::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', WebhookSubscriptionStatus::Active)
            ->get();

        foreach ($subscriptions as $subscription) {
            $delivery = new WebhookDelivery;
            $delivery->fill([
                'subscription_id' => $subscription->id,
                'event_id' => Str::uuid()->toString(),
                'attempt_count' => 0,
                'status' => 'pending',
            ]);
            $delivery->save();

            DeliverWebhook::dispatch($delivery, $payload);
        }
    }

    /**
     * Generate HMAC-SHA256 signature for a payload.
     */
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify an HMAC-SHA256 signature.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        $expected = $this->sign($payload, $secret);

        return hash_equals($expected, $signature);
    }
}
