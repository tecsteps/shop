<?php

namespace App\Jobs;

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

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $deliveryId,
        public string $eventType,
        public array $payload,
    ) {}

    public function handle(WebhookService $webhooks): void
    {
        $delivery = WebhookDelivery::query()->with('subscription')->findOrFail($this->deliveryId);
        $subscription = $delivery->subscription;

        $body = json_encode($webhooks->payload($this->eventType, $this->payload), JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = $webhooks->signWithTimestamp($body, $subscription->signing_secret_encrypted, $timestamp);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => (string) $delivery->id,
                    'X-Platform-Timestamp' => (string) $timestamp,
                ])
                ->withBody($body, 'application/json')
                ->post($subscription->target_url);
        } catch (Throwable $exception) {
            $this->markFailed($delivery, $subscription, null, $exception->getMessage());

            throw $exception;
        }

        if ($response->successful()) {
            $delivery->forceFill([
                'attempt_count' => $this->attempts(),
                'status' => 'success',
                'last_attempt_at' => now(),
                'response_code' => $response->status(),
                'response_body_snippet' => str($response->body())->limit(1000)->toString(),
            ])->save();

            $subscription->forceFill(['consecutive_failures' => 0])->save();

            return;
        }

        $this->markFailed($delivery, $subscription, $response->status(), $response->body());

        throw new RuntimeException('Webhook delivery failed with status '.$response->status().'.');
    }

    private function markFailed(WebhookDelivery $delivery, WebhookSubscription $subscription, ?int $statusCode, string $body): void
    {
        $failures = $subscription->consecutive_failures + 1;

        $delivery->forceFill([
            'attempt_count' => $this->attempts(),
            'status' => 'failed',
            'last_attempt_at' => now(),
            'response_code' => $statusCode,
            'response_body_snippet' => str($body)->limit(1000)->toString(),
        ])->save();

        $subscription->forceFill([
            'consecutive_failures' => $failures,
            'status' => $failures >= 5 ? 'paused' : $subscription->status,
        ])->save();
    }
}
