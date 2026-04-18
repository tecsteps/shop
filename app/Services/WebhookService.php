<?php

namespace App\Services;

use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatchEvent(int $storeId, string $eventType, array $payload): int
    {
        $subscriptions = WebhookSubscription::query()
            ->where('store_id', $storeId)
            ->where('event_type', $eventType)
            ->where('status', WebhookSubscriptionStatus::Active)
            ->get();

        foreach ($subscriptions as $subscription) {
            $eventId = (string) Str::uuid();
            DeliverWebhook::dispatch($subscription->id, $eventType, $payload, $eventId);
        }

        return $subscriptions->count();
    }

    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }

    public function decryptSecret(WebhookSubscription $subscription): string
    {
        return Crypt::decryptString($subscription->signing_secret_encrypted);
    }
}
