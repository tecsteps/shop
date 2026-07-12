<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;
use Throwable;

final class WebhookService
{
    private OutboundDispatcher $outbound;

    public function __construct(?OutboundDispatcher $outbound = null)
    {
        $this->outbound = $outbound ?? app(OutboundDispatcher::class);
    }

    /** @param array<string, mixed> $payload */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $storeId = (int) $store->id;
        $this->outbound->afterCommit(function () use ($storeId, $eventType, $payload): void {
            WebhookSubscription::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->where('event_type', $eventType)
                ->where('status', 'active')
                ->each(function (WebhookSubscription $subscription) use ($eventType, $payload): void {
                    try {
                        $delivery = $subscription->deliveries()->create([
                            'event_id' => (string) Str::uuid(),
                            'attempt_count' => 0,
                            'status' => 'pending',
                        ]);
                        DeliverWebhook::dispatch($delivery, $eventType, $payload);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                });
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
