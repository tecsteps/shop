<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Delivers a single webhook payload to a subscription's target URL.
 *
 * The JSON body is signed with HMAC-SHA256 and sent with the platform headers
 * (`X-Platform-Signature`, `-Event`, `-Delivery-Id`, `-Timestamp`). Failures
 * (transport errors or non-2xx responses) retry with exponential backoff
 * (1m, 5m, 30m, 2h, 12h — 6 attempts total). After the attempts are exhausted
 * the delivery is marked failed; a circuit breaker pauses the subscription once
 * it accumulates {@see self::CIRCUIT_BREAKER_THRESHOLD} consecutive failures.
 */
class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Total attempts (1 initial + 5 retries).
     */
    public int $tries = 6;

    /**
     * Consecutive failed deliveries that trip the circuit breaker.
     */
    public const CIRCUIT_BREAKER_THRESHOLD = 5;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $deliveryId,
        public string $eventType,
        public array $payload,
    ) {}

    /**
     * Exponential backoff between retries, in seconds.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function handle(WebhookService $webhooks): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $subscription = $delivery->subscription;

        if ($subscription === null || $subscription->status === WebhookSubscriptionStatus::Disabled) {
            return;
        }

        $body = json_encode([
            'event' => $this->eventType,
            'delivery_id' => $delivery->event_id,
            'api_version' => 'v1',
            'data' => $this->payload,
        ], JSON_THROW_ON_ERROR);

        $signature = $webhooks->sign($body, $subscription->signing_secret_encrypted);
        $timestamp = (string) Carbon::now()->getTimestamp();

        $delivery->increment('attempt_count');
        $delivery->update(['last_attempt_at' => Carbon::now()]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Platform-Signature' => $signature,
                'X-Platform-Event' => $this->eventType,
                'X-Platform-Delivery-Id' => $delivery->event_id,
                'X-Platform-Timestamp' => $timestamp,
            ])->withBody($body, 'application/json')->post($subscription->target_url);
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => WebhookDeliveryStatus::Pending->value,
                'response_code' => null,
                'response_body_snippet' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

            throw $exception;
        }

        $delivery->update([
            'response_code' => $response->status(),
            'response_body_snippet' => mb_substr($response->body(), 0, 1000),
        ]);

        if ($response->successful()) {
            $delivery->update(['status' => WebhookDeliveryStatus::Success->value]);

            return;
        }

        $delivery->update(['status' => WebhookDeliveryStatus::Pending->value]);

        throw new RuntimeException(
            "Webhook delivery {$delivery->id} failed with HTTP {$response->status()}.",
        );
    }

    /**
     * Invoked after all attempts are exhausted: mark the delivery failed and
     * apply the circuit breaker to its subscription.
     */
    public function failed(?Throwable $exception): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $delivery->update(['status' => WebhookDeliveryStatus::Failed->value]);

        $this->tripCircuitBreaker($delivery);
    }

    /**
     * Pause the subscription when its most recent deliveries are all failures.
     */
    private function tripCircuitBreaker(WebhookDelivery $delivery): void
    {
        $subscription = $delivery->subscription;

        if ($subscription === null || $subscription->status !== WebhookSubscriptionStatus::Active) {
            return;
        }

        $recent = WebhookDelivery::query()
            ->where('subscription_id', $subscription->id)
            ->orderByDesc('id')
            ->limit(self::CIRCUIT_BREAKER_THRESHOLD)
            ->pluck('status');

        $allFailed = $recent->count() >= self::CIRCUIT_BREAKER_THRESHOLD
            && $recent->every(fn ($status): bool => $status === WebhookDeliveryStatus::Failed);

        if ($allFailed) {
            $subscription->update(['status' => WebhookSubscriptionStatus::Paused->value]);
        }
    }
}
