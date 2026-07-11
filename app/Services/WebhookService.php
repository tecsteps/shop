<?php

namespace App\Services;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookService
{
    /** @param array<string, mixed> $payload */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        WebhookSubscription::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->where('event_type', $eventType)
            ->where('status', WebhookSubscriptionStatus::Active)
            ->each(function (WebhookSubscription $subscription) use ($eventType, $payload): void {
                $eventId = (string) Str::uuid();
                $timestamp = now()->getTimestamp();
                $envelope = [
                    'id' => $eventId,
                    'type' => $eventType,
                    'created_at' => now()->toIso8601String(),
                    'data' => $payload,
                ];
                $delivery = $subscription->deliveries()->create([
                    'event_id' => $eventId,
                    'attempt_count' => 1,
                    'status' => WebhookDeliveryStatus::Pending,
                ]);

                DeliverWebhook::dispatch($delivery->id, $envelope, $timestamp);
            });
    }

    public function sign(string $payload, string $secret, ?int $timestamp = null): string
    {
        return hash_hmac('sha256', ($timestamp ?? 0).'.'.$payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret, ?int $timestamp = null): bool
    {
        return hash_equals($this->sign($payload, $secret, $timestamp), $signature);
    }

    public function recordFailure(WebhookSubscription $subscription): void
    {
        $statuses = $subscription->deliveries()
            ->latest('id')
            ->limit(5)
            ->pluck('status');

        if ($statuses->count() !== 5 || $statuses->contains(
            fn (WebhookDeliveryStatus|string $status): bool => $status !== WebhookDeliveryStatus::Failed
                && $status !== WebhookDeliveryStatus::Failed->value,
        )) {
            return;
        }

        $subscription->update(['status' => WebhookSubscriptionStatus::Paused]);

        Log::warning('Webhook subscription paused after consecutive failures.', [
            'subscription_id' => $subscription->id,
            'store_id' => $subscription->store_id,
        ]);
    }
}
