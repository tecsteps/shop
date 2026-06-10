<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\Scopes\StoreScope;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * POSTs a signed webhook payload to a subscriber endpoint (spec 05 sections
 * 13.2-13.4). Retries with exponential backoff via Laravel's job retry
 * mechanism; the delivery record tracks attempt accounting and the
 * subscription tracks consecutive failures for the circuit breaker.
 */
class DeliverWebhook implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * 1 initial attempt + 5 retries (spec 05 section 13.3).
     */
    public int $tries = self::MAX_ATTEMPTS;

    /**
     * Backoff delays in seconds: 1 min, 5 min, 30 min, 2 h, 12 h.
     *
     * @var list<int>
     */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public const int MAX_ATTEMPTS = 6;

    /**
     * Consecutive failed attempts before the subscription is paused
     * (spec 05 section 13.4).
     */
    public const int CIRCUIT_BREAKER_THRESHOLD = 5;

    public const int RESPONSE_SNIPPET_LENGTH = 500;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public WebhookDelivery $delivery,
        public array $payload,
        public int $eventTimestamp,
    ) {}

    public function handle(WebhookService $webhooks): void
    {
        $delivery = $this->delivery->fresh();

        if ($delivery === null || $delivery->status !== WebhookDeliveryStatus::Pending) {
            return;
        }

        $subscription = WebhookSubscription::query()
            ->withoutGlobalScope(StoreScope::class)
            ->find($delivery->subscription_id);

        if ($subscription === null || $subscription->status !== WebhookSubscriptionStatus::Active) {
            $delivery->update(['status' => WebhookDeliveryStatus::Failed]);

            return;
        }

        $body = json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        [$responseCode, $responseSnippet] = $this->attemptDelivery($subscription, $webhooks, $body);

        $attempt = $delivery->attempt_count + 1;
        $succeeded = $responseCode !== null && $responseCode >= 200 && $responseCode < 300;
        $exhausted = $attempt >= self::MAX_ATTEMPTS;

        $delivery->update([
            'attempt_count' => $attempt,
            'status' => match (true) {
                $succeeded => WebhookDeliveryStatus::Success,
                $exhausted => WebhookDeliveryStatus::Failed,
                default => WebhookDeliveryStatus::Pending,
            },
            'last_attempt_at' => now(),
            'response_code' => $responseCode,
            'response_body_snippet' => $responseSnippet,
        ]);

        if ($succeeded) {
            $this->recordSuccess($subscription);

            return;
        }

        $this->recordFailure($subscription);

        if (! $exhausted) {
            throw new RuntimeException(sprintf(
                'Webhook delivery %d to %s failed with status %s.',
                $delivery->getKey(),
                $subscription->target_url,
                $responseCode ?? 'connection error',
            ));
        }
    }

    /**
     * POST the signed payload and normalize the outcome to a response code
     * (null on connection failure) and a truncated body snippet.
     *
     * @return array{0: int|null, 1: string|null}
     */
    protected function attemptDelivery(WebhookSubscription $subscription, WebhookService $webhooks, string $body): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Platform-Signature' => $webhooks->sign($body, $subscription->signing_secret_encrypted),
                    'X-Platform-Event' => $subscription->event_type,
                    'X-Platform-Delivery-Id' => (string) Str::uuid(),
                    'X-Platform-Timestamp' => (string) $this->eventTimestamp,
                ])
                ->withBody($body, 'application/json')
                ->post($subscription->target_url);

            return [
                $response->status(),
                Str::limit($response->body(), self::RESPONSE_SNIPPET_LENGTH, ''),
            ];
        } catch (ConnectionException $exception) {
            return [
                null,
                Str::limit($exception->getMessage(), self::RESPONSE_SNIPPET_LENGTH, ''),
            ];
        }
    }

    /**
     * A successful delivery resets the circuit breaker counter
     * (spec 05 section 13.4).
     */
    protected function recordSuccess(WebhookSubscription $subscription): void
    {
        if ($subscription->consecutive_failures > 0) {
            $subscription->update(['consecutive_failures' => 0]);
        }
    }

    /**
     * Each failed attempt increments the circuit breaker counter; at the
     * threshold the subscription is paused until a merchant resumes it
     * manually through the admin UI (spec 05 section 13.4).
     */
    protected function recordFailure(WebhookSubscription $subscription): void
    {
        $failures = $subscription->consecutive_failures + 1;

        $attributes = ['consecutive_failures' => $failures];

        if ($failures >= self::CIRCUIT_BREAKER_THRESHOLD) {
            $attributes['status'] = WebhookSubscriptionStatus::Paused;

            Log::warning('Webhook subscription paused by circuit breaker after consecutive failures.', [
                'subscription_id' => $subscription->getKey(),
                'store_id' => $subscription->store_id,
                'target_url' => $subscription->target_url,
                'consecutive_failures' => $failures,
            ]);
        }

        $subscription->update($attributes);
    }
}
