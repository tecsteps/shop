<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST a webhook payload to a subscriber endpoint per spec 05 section 13.2-13.3.
 * Retries with backoff: 1 min, 5 min, 30 min, 2 h, 12 h (6 total attempts).
 */
class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $deliveryId,
        public string $eventType,
        public array $payload,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function handle(WebhookService $webhooks): void
    {
        $delivery = WebhookDelivery::query()->with('subscription')->find($this->deliveryId);

        if ($delivery === null || $delivery->subscription === null) {
            return;
        }

        $subscription = $delivery->subscription;

        if ($subscription->status !== WebhookSubscriptionStatus::Active) {
            $delivery->status = WebhookDeliveryStatus::Failed;
            $delivery->last_attempt_at = now();
            $delivery->save();

            return;
        }

        $body = json_encode($this->payload);
        $secret = (string) $subscription->signing_secret_encrypted;
        $timestamp = (string) now()->timestamp;

        $delivery->attempt_count = $delivery->attempt_count + 1;
        $delivery->last_attempt_at = now();

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Platform-Signature' => $webhooks->sign($body, $secret),
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => $delivery->event_id,
                    'X-Platform-Timestamp' => $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($subscription->target_url);

            $delivery->response_code = $response->status();
            $delivery->response_body_snippet = mb_substr((string) $response->body(), 0, 2000);

            if ($response->successful()) {
                $delivery->status = WebhookDeliveryStatus::Success;
                $delivery->save();
                $webhooks->recordSuccess($subscription);

                return;
            }

            $delivery->save();
            $this->failOrRetry($delivery, $webhooks);
        } catch (Throwable $exception) {
            $delivery->response_code = null;
            $delivery->response_body_snippet = mb_substr($exception->getMessage(), 0, 2000);
            $delivery->save();
            $this->failOrRetry($delivery, $webhooks);
        }
    }

    public function failed(?Throwable $exception = null): void
    {
        $delivery = WebhookDelivery::query()->with('subscription')->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $delivery->status = WebhookDeliveryStatus::Failed;
        $delivery->last_attempt_at = now();

        if ($exception !== null && $delivery->response_body_snippet === null) {
            $delivery->response_body_snippet = mb_substr($exception->getMessage(), 0, 2000);
        }

        $delivery->save();

        if ($delivery->subscription !== null) {
            app(WebhookService::class)->recordFailure($delivery->subscription);

            Log::warning('Webhook delivery failed permanently', [
                'delivery_id' => $delivery->getKey(),
                'subscription_id' => $delivery->subscription_id,
            ]);
        }
    }

    protected function failOrRetry(WebhookDelivery $delivery, WebhookService $webhooks): void
    {
        if ($delivery->attempt_count >= $this->tries) {
            $delivery->status = WebhookDeliveryStatus::Failed;
            $delivery->save();
            $webhooks->recordFailure($delivery->subscription);

            Log::warning('Webhook delivery failed permanently', [
                'delivery_id' => $delivery->getKey(),
                'subscription_id' => $delivery->subscription_id,
            ]);

            return;
        }

        $this->release($this->backoff()[$delivery->attempt_count - 1] ?? 60);
    }
}
