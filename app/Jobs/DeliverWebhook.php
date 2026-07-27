<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Delivers one webhook event to a subscription's target URL, records the
 * outcome, retries with backoff, and trips the circuit breaker after too
 * many consecutive failures (spec 05 §13.2-§13.4).
 */
class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * 1 initial attempt + 5 retries (spec 05 §13.3).
     */
    public int $tries = 6;

    /**
     * Pause the subscription after this many consecutive failed
     * deliveries (spec 05 §13.4).
     */
    public const CIRCUIT_BREAKER_THRESHOLD = 5;

    /**
     * Unique delivery id, sent as X-Platform-Delivery-Id and stored as
     * the delivery's event_id.
     */
    public string $deliveryId;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public WebhookSubscription $subscription,
        public string $eventType,
        public array $payload,
        ?string $deliveryId = null,
    ) {
        $this->deliveryId = $deliveryId ?? (string) Str::uuid();
    }

    /**
     * Retry delays in seconds: 1 min, 5 min, 30 min, 2 h, 12 h
     * (spec 05 §13.3).
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    /**
     * POST the signed JSON payload to the target URL and record the
     * result in webhook_deliveries.
     *
     * @throws RuntimeException when the endpoint failed and retries remain
     */
    public function handle(WebhookService $webhooks): void
    {
        $this->subscription->refresh();

        // Paused or disabled subscriptions receive no further deliveries.
        if ($this->subscription->status !== WebhookSubscriptionStatus::Active) {
            return;
        }

        $delivery = WebhookDelivery::query()->firstOrCreate(
            ['subscription_id' => $this->subscription->id, 'event_id' => $this->deliveryId],
            ['status' => WebhookDeliveryStatus::Pending, 'attempt_count' => 0],
        );

        $attempt = $this->attempts() ?? 1;
        $timestamp = time();
        $body = (string) json_encode($this->payload);

        $statusCode = null;
        $snippet = null;
        $successful = false;

        try {
            $response = Http::withBody($body, 'application/json')
                ->withHeaders([
                    'X-Platform-Signature' => $webhooks->sign($body, $this->subscription->signing_secret_encrypted, $timestamp),
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => $this->deliveryId,
                    'X-Platform-Timestamp' => (string) $timestamp,
                ])
                ->timeout(10)
                ->post($this->subscription->target_url);

            $statusCode = $response->status();
            $snippet = Str::limit($response->body(), 500, '');
            $successful = $response->successful();
        } catch (ConnectionException $exception) {
            $snippet = Str::limit($exception->getMessage(), 500, '');
        }

        $delivery->forceFill([
            'attempt_count' => $attempt,
            'status' => $successful ? WebhookDeliveryStatus::Success : WebhookDeliveryStatus::Failed,
            'response_code' => $statusCode,
            'response_body_snippet' => $snippet,
            'last_attempt_at' => now(),
        ])->save();

        if ($successful) {
            return;
        }

        $this->tripCircuitBreakerIfNeeded();

        if ($attempt < $this->tries) {
            throw new RuntimeException("Webhook delivery {$this->deliveryId} failed (response code: ".($statusCode ?? 'none').').');
        }
    }

    /**
     * Pause the subscription once the consecutive failure streak reaches
     * the threshold; manual re-enable is required (spec 05 §13.4).
     */
    private function tripCircuitBreakerIfNeeded(): void
    {
        if ($this->subscription->consecutiveFailures() < self::CIRCUIT_BREAKER_THRESHOLD) {
            return;
        }

        $this->subscription->forceFill(['status' => WebhookSubscriptionStatus::Paused])->save();

        Log::warning('Webhook subscription paused after consecutive delivery failures', [
            'subscription_id' => $this->subscription->id,
            'store_id' => $this->subscription->store_id,
            'target_url' => $this->subscription->target_url,
        ]);
    }
}
