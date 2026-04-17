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

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public function __construct(
        protected int $deliveryId,
        protected array $payload,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $delivery = WebhookDelivery::findOrFail($this->deliveryId);
        $subscription = $delivery->subscription;

        $jsonPayload = json_encode($this->payload);
        $secret = $subscription->signing_secret_encrypted;
        $signature = $webhookService->sign($jsonPayload, $secret);

        $delivery->increment('attempt_count');
        $delivery->update(['last_attempt_at' => now()]);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $subscription->event_type,
                    'X-Platform-Delivery-Id' => $delivery->event_id,
                    'X-Platform-Timestamp' => now()->toIso8601String(),
                    'Content-Type' => 'application/json',
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($subscription->target_url);

            $delivery->update([
                'response_code' => $response->status(),
                'response_body_snippet' => mb_substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $delivery->update(['status' => WebhookDeliveryStatus::Success]);
                $this->resetConsecutiveFailures($subscription);

                return;
            }

            throw new \RuntimeException("Webhook delivery failed with status {$response->status()}");
        } catch (\Throwable $e) {
            if ($delivery->attempt_count >= $this->tries) {
                $delivery->update(['status' => WebhookDeliveryStatus::Failed]);
                $this->checkCircuitBreaker($subscription);

                return;
            }

            $this->release($this->backoff[$delivery->attempt_count - 1] ?? 43200);
        }
    }

    protected function checkCircuitBreaker(WebhookSubscription $subscription): void
    {
        $recentFailures = $subscription->deliveries()
            ->latest('last_attempt_at')
            ->limit(5)
            ->get();

        if ($recentFailures->count() >= 5 && $recentFailures->every(fn ($d) => $d->status === WebhookDeliveryStatus::Failed)) {
            $subscription->update(['status' => WebhookSubscriptionStatus::Paused]);
        }
    }

    protected function resetConsecutiveFailures(WebhookSubscription $subscription): void
    {
        // Success resets the circuit breaker - no action needed since we check consecutive failures
    }
}
