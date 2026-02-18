<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /**
     * Exponential backoff: 1 min, 5 min, 30 min, 2 h, 12 h.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public WebhookDelivery $delivery,
        public array $payload
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $subscription = $this->delivery->subscription;
        $payloadJson = json_encode($this->payload) ?: '{}';
        $secret = $subscription->signing_secret_encrypted;
        $signature = $webhookService->sign($payloadJson, $secret);

        $this->delivery->update(['attempt_count' => $this->delivery->attempt_count + 1]);
        $this->delivery->update(['last_attempt_at' => now()]);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $subscription->event_type,
                    'X-Platform-Delivery-Id' => $this->delivery->event_id,
                    'X-Platform-Timestamp' => now()->toIso8601String(),
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($subscription->target_url);

            $this->delivery->update([
                'response_code' => $response->status(),
                'response_body_snippet' => mb_substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $this->delivery->update([
                    'status' => WebhookDeliveryStatus::Success,
                ]);

                Log::channel('json')->info('Webhook delivered', [
                    'delivery_id' => $this->delivery->id,
                    'subscription_id' => $subscription->id,
                    'event_type' => $subscription->event_type,
                    'target_url' => $subscription->target_url,
                    'response_code' => $response->status(),
                ]);

                return;
            }

            $this->handleFailure($subscription);
        } catch (\Throwable $e) {
            $this->delivery->update([
                'response_body_snippet' => mb_substr($e->getMessage(), 0, 500),
            ]);

            $this->handleFailure($subscription);
        }
    }

    /**
     * Handle the job failure after all retries exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        $this->delivery->update([
            'status' => WebhookDeliveryStatus::Failed,
        ]);

        Log::channel('json')->warning('Webhook delivery failed', [
            'delivery_id' => $this->delivery->id,
            'subscription_id' => $this->delivery->subscription_id,
            'attempt_count' => $this->delivery->attempt_count,
            'error' => $exception?->getMessage(),
        ]);

        $this->checkCircuitBreaker($this->delivery->subscription);
    }

    protected function handleFailure(WebhookSubscription $subscription): void
    {
        $this->delivery->refresh();

        if ($this->delivery->attempt_count >= $this->tries) {
            $this->delivery->update([
                'status' => WebhookDeliveryStatus::Failed,
            ]);

            $this->checkCircuitBreaker($subscription);

            return;
        }

        // When running via queue, release for retry
        $backoffIndex = $this->delivery->attempt_count - 1;
        if ($this->job) {
            $this->release($this->backoff[$backoffIndex] ?? 43200);
        }
    }

    protected function checkCircuitBreaker(WebhookSubscription $subscription): void
    {
        $consecutiveFailures = WebhookDelivery::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', WebhookDeliveryStatus::Failed)
            ->latest('last_attempt_at')
            ->limit(5)
            ->count();

        if ($consecutiveFailures >= 5) {
            $subscription->update([
                'status' => WebhookSubscriptionStatus::Paused,
            ]);
        }
    }
}
