<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /** @var array<int> */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $deliveryId,
        public array $payload,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        if (! $delivery) {
            return;
        }

        $subscription = $delivery->subscription;
        if (! $subscription || $subscription->status->value !== 'active') {
            return;
        }

        $jsonPayload = json_encode($this->payload);
        $signature = $webhookService->sign($jsonPayload, $subscription->signing_secret_encrypted);
        $timestamp = now()->toIso8601String();

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $subscription->event_type,
                    'X-Platform-Delivery-Id' => $delivery->event_id,
                    'X-Platform-Timestamp' => $timestamp,
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($subscription->target_url);

            $delivery->update([
                'attempt_count' => $delivery->attempt_count + 1,
                'last_attempt_at' => now(),
                'response_code' => $response->status(),
                'response_body_snippet' => mb_substr($response->body(), 0, 500),
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if (! $response->successful()) {
                $this->checkCircuitBreaker($subscription);
                $this->fail(new \RuntimeException('Webhook delivery failed with status '.$response->status()));
            }
        } catch (\Exception $e) {
            $delivery->update([
                'attempt_count' => $delivery->attempt_count + 1,
                'last_attempt_at' => now(),
                'status' => 'failed',
                'response_body_snippet' => mb_substr($e->getMessage(), 0, 500),
            ]);

            $this->checkCircuitBreaker($subscription);

            throw $e;
        }
    }

    protected function checkCircuitBreaker(WebhookSubscription $subscription): void
    {
        $recentFailures = WebhookDelivery::where('subscription_id', $subscription->id)
            ->where('status', 'failed')
            ->orderByDesc('id')
            ->limit(5)
            ->count();

        if ($recentFailures >= 5) {
            $subscription->update(['status' => 'paused']);
        }
    }
}
