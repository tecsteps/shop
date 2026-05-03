<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $eventId = (string) Str::uuid();

        WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->get()
            ->each(function (WebhookSubscription $subscription) use ($eventId, $eventType, $payload): void {
                $delivery = WebhookDelivery::query()->create([
                    'subscription_id' => $subscription->id,
                    'event_id' => $eventId,
                ]);

                DeliverWebhook::dispatch($delivery->id, $eventType, $payload);
            });
    }

    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function signWithTimestamp(string $payload, string $secret, int $timestamp): string
    {
        return $this->sign($timestamp.'.'.$payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function payload(string $eventType, array $payload): array
    {
        return [
            'api_version' => '2026-05',
            'event' => $eventType,
            'timestamp' => now()->toISOString(),
            'data' => $payload,
        ];
    }
}
