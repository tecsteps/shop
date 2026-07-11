<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public int $timeout = 15;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public int $deliveryId,
        public array $payload,
        public int $timestamp,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhooks): void
    {
        $delivery = WebhookDelivery::query()->with('subscription')->findOrFail($this->deliveryId);
        $subscription = $delivery->subscription;

        if ($subscription->status !== WebhookSubscriptionStatus::Active) {
            $delivery->update(['status' => WebhookDeliveryStatus::Failed]);

            return;
        }

        $json = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $delivery->update([
            'attempt_count' => max($delivery->attempt_count, $this->attempts()),
            'last_attempt_at' => now(),
        ]);

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withHeaders([
                    'X-Platform-Signature' => $webhooks->sign($json, $subscription->signing_secret_encrypted, $this->timestamp),
                    'X-Platform-Event' => $subscription->event_type,
                    'X-Platform-Delivery-Id' => $delivery->event_id,
                    'X-Platform-Timestamp' => (string) $this->timestamp,
                ])
                ->withBody($json, 'application/json')
                ->post($subscription->target_url);
        } catch (Throwable $exception) {
            $this->recordFailure($delivery, null, null, $webhooks);

            throw $exception;
        }

        if ($response->failed()) {
            $this->recordFailure($delivery, $response, $response->body(), $webhooks);
            $response->throw();
        }

        $delivery->update([
            'status' => WebhookDeliveryStatus::Success,
            'response_code' => $response->status(),
            'response_body_snippet' => Str::limit($response->body(), 1000, ''),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        WebhookDelivery::query()->whereKey($this->deliveryId)->update([
            'status' => WebhookDeliveryStatus::Failed,
        ]);
    }

    private function recordFailure(
        WebhookDelivery $delivery,
        ?Response $response,
        ?string $body,
        WebhookService $webhooks,
    ): void {
        $delivery->update([
            'status' => WebhookDeliveryStatus::Failed,
            'response_code' => $response?->status(),
            'response_body_snippet' => $body === null ? null : Str::limit($body, 1000, ''),
        ]);

        $webhooks->recordFailure($delivery->subscription);
    }
}
