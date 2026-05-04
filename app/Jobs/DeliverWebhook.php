<?php

namespace App\Jobs;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public int $timeout = 15;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $deliveryId,
        public string $eventType,
        public array $payload,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhooks): void
    {
        $delivery = WebhookDelivery::query()
            ->with('subscription')
            ->findOrFail($this->deliveryId);
        $subscription = $delivery->subscription;

        if (! $subscription instanceof WebhookSubscription || $subscription->status !== WebhookSubscriptionStatus::Active) {
            return;
        }

        $attemptCount = max(1, $this->attempts());
        $body = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = (string) now()->timestamp;
        $signature = $webhooks->sign($timestamp.'.'.$body, (string) $subscription->signing_secret_encrypted);
        $recordedFailure = false;

        try {
            $response = Http::timeout(5)
                ->connectTimeout(2)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => $delivery->event_id,
                    'X-Platform-Timestamp' => $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($subscription->target_url);

            if ($response->successful()) {
                $webhooks->recordSuccess($delivery, $attemptCount, $response->status(), $response->body());

                return;
            }

            $webhooks->recordFailure($delivery, $attemptCount, $response->status(), $response->body());
            $recordedFailure = true;

            throw new RuntimeException("Webhook delivery failed with HTTP {$response->status()}.");
        } catch (Throwable $throwable) {
            if (! $recordedFailure) {
                $webhooks->recordFailure($delivery, $attemptCount, null, $throwable->getMessage());
            }

            throw $throwable;
        }
    }
}
