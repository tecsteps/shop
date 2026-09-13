<?php

namespace App\Services;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

/**
 * Outbound webhook subscription management and dispatch per spec 05 section 13.
 */
class WebhookService
{
    public const EVENT_TYPES = [
        'order.created',
        'order.paid',
        'order.fulfilled',
        'order.refunded',
        'order.updated',
        'order.cancelled',
        'product.created',
        'product.updated',
        'product.deleted',
        'customer.created',
        'checkout.completed',
        'fulfillment.created',
        'refund.created',
    ];

    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }

    public function subscribe(
        Store $store,
        string $eventType,
        string $targetUrl,
        ?int $appInstallationId = null,
    ): WebhookSubscription {
        return WebhookSubscription::query()->create([
            'store_id' => $store->getKey(),
            'app_installation_id' => $appInstallationId,
            'event_type' => $eventType,
            'target_url' => $targetUrl,
            'signing_secret_encrypted' => Str::random(48),
            'status' => WebhookSubscriptionStatus::Active,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $subscriptions = WebhookSubscription::query()
            ->where('store_id', $store->getKey())
            ->where('event_type', $eventType)
            ->where('status', WebhookSubscriptionStatus::Active)
            ->get();

        foreach ($subscriptions as $subscription) {
            $delivery = WebhookDelivery::query()->create([
                'subscription_id' => $subscription->getKey(),
                'event_id' => (string) Str::uuid(),
                'attempt_count' => 0,
                'status' => WebhookDeliveryStatus::Pending,
            ]);

            DeliverWebhook::dispatch($delivery->getKey(), $eventType, $payload);
        }
    }

    public function recordSuccess(WebhookSubscription $subscription): void
    {
        if ($subscription->status === WebhookSubscriptionStatus::Paused) {
            return;
        }

        $subscription->status = WebhookSubscriptionStatus::Active;
        $subscription->save();
    }

    /**
     * Circuit breaker per spec 05 section 13.4: after 5 consecutive failed
     * deliveries for a subscription, pause it until a merchant re-enables it.
     */
    public function recordFailure(WebhookSubscription $subscription): void
    {
        $recent = WebhookDelivery::query()
            ->where('subscription_id', $subscription->getKey())
            ->orderByDesc('id')
            ->limit(5)
            ->pluck('status');

        if ($recent->count() < 5) {
            return;
        }

        $allFailed = $recent->every(fn ($status): bool => $status === WebhookDeliveryStatus::Failed
            || $status === WebhookDeliveryStatus::Failed->value);

        if ($allFailed) {
            $subscription->status = WebhookSubscriptionStatus::Paused;
            $subscription->save();
        }
    }
}
