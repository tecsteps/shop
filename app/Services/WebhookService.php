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
        $subscriptions = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->get();

        foreach ($subscriptions as $subscription) {
            $eventId = Str::uuid()->toString();

            $delivery = WebhookDelivery::create([
                'subscription_id' => $subscription->id,
                'event_id' => $eventId,
                'attempt_count' => 0,
                'status' => 'pending',
            ]);

            DeliverWebhook::dispatch($delivery);
        }
    }

    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret): bool
    {
        $expected = $this->sign($payload, $secret);

        return hash_equals($expected, $signature);
    }
}
