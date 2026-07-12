<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

final class WebhookService
{
    /** @param array<string, mixed> $payload */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->each(function (WebhookSubscription $subscription) use ($eventType, $payload): void {
                $delivery = $subscription->deliveries()->create([
                    'event_id' => (string) Str::uuid(),
                    'attempt_count' => 0,
                    'status' => 'pending',
                ]);
                DeliverWebhook::dispatch($delivery, $eventType, $payload);
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

    public function recordFailure(WebhookDelivery $delivery): void
    {
        $subscription = $delivery->subscription;
        $recent = $subscription->deliveries()->latest('id')->limit(5)->get()->pluck('status');
        if ($recent->count() === 5 && $recent->every(fn (mixed $status): bool => ($status instanceof \BackedEnum ? $status->value : $status) === 'failed')) {
            $subscription->update(['status' => 'paused']);
        }
    }
}
