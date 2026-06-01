<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Jobs\SyncJob;
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
 * (1m, 5m, 30m, 2h, 12h — 6 attempts total) when running on a real async queue;
 * after the attempts are exhausted the delivery is marked failed and a circuit
 * breaker pauses the subscription once it accumulates
 * {@see self::CIRCUIT_BREAKER_THRESHOLD} consecutive failures.
 *
 * CRITICAL: webhook delivery MUST NOT break the request that dispatched it. With
 * `QUEUE_CONNECTION=sync` the job runs inline inside order creation, so a
 * failure here is handled terminally (recorded + circuit breaker) and swallowed
 * rather than re-thrown — an unreachable endpoint can never 500 a checkout.
 */
class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Total attempts (1 initial + 5 retries).
     */
    public int $tries = 6;

    /**
     * HTTP request timeout in seconds — kept short so a hung endpoint can never
     * stall the dispatching request (which runs inline under the sync queue).
     */
    public const REQUEST_TIMEOUT = 5;

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
            ])
                ->timeout(self::REQUEST_TIMEOUT)
                ->withBody($body, 'application/json')
                ->post($subscription->target_url);
        } catch (Throwable $exception) {
            // Transport failure (DNS, connection refused, timeout). Record it,
            // then either retry on a real queue or terminate inline under sync.
            $delivery->update([
                'response_code' => null,
                'response_body_snippet' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

            $this->handleFailure($delivery, $exception);

            return;
        }

        $delivery->update([
            'response_code' => $response->status(),
            'response_body_snippet' => mb_substr($response->body(), 0, 1000),
        ]);

        if ($response->successful()) {
            $delivery->update(['status' => WebhookDeliveryStatus::Success->value]);

            return;
        }

        $this->handleFailure($delivery, new RuntimeException(
            "Webhook delivery {$delivery->id} failed with HTTP {$response->status()}.",
        ));
    }

    /**
     * Route a delivery failure.
     *
     * On a real async queue with retries remaining: leave the delivery pending
     * and re-throw so the queue applies backoff and eventually calls
     * {@see failed()}. Running synchronously (or out of attempts): handle
     * terminally — mark failed, trip the circuit breaker — and SWALLOW the
     * exception so the dispatching request (e.g. order creation) still succeeds.
     */
    private function handleFailure(WebhookDelivery $delivery, Throwable $exception): void
    {
        if ($this->shouldRetry()) {
            $delivery->update(['status' => WebhookDeliveryStatus::Pending->value]);

            throw $exception;
        }

        $this->recordTerminalFailure($delivery);
    }

    /**
     * Whether this run should re-throw to let the queue retry. Only true on a
     * real async queue with attempts remaining; never under the sync driver,
     * where a throw would propagate into the dispatching web request.
     */
    private function shouldRetry(): bool
    {
        if ($this->runningSynchronously()) {
            return false;
        }

        $maxTries = $this->tries ?: 1;

        return $this->attempts() < $maxTries;
    }

    /**
     * Whether the job is executing inline rather than on a real async queue, so
     * a thrown exception would surface in the dispatching request rather than
     * being retried by the queue.
     *
     * True when wrapped in a {@see SyncJob} (the `sync` queue driver — the
     * production hazard) or when invoked with no queue job attached (e.g. a
     * direct `->handle()` call). A real async driver attaches a non-sync job, so
     * the retry/backoff path stays intact there.
     */
    private function runningSynchronously(): bool
    {
        return $this->job === null || $this->job instanceof SyncJob;
    }

    /**
     * Invoked after all async attempts are exhausted: mark failed + circuit
     * breaker. (Sync failures terminate via {@see handleFailure()} instead.)
     */
    public function failed(?Throwable $exception): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $this->recordTerminalFailure($delivery);
    }

    /**
     * Mark the delivery failed and apply the circuit breaker. Never throws.
     */
    private function recordTerminalFailure(WebhookDelivery $delivery): void
    {
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
