<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /** @var int[] */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public function __construct(public WebhookDelivery $delivery) {}

    public function handle(WebhookService $webhookService): void
    {
        $delivery = $this->delivery;
        $subscription = $delivery->subscription;

        if (! $subscription || $subscription->status !== 'active') {
            return;
        }

        $payload = json_encode([
            'event_type' => $subscription->event_type,
            'event_id' => $delivery->event_id,
            'data' => [],
        ]);

        $secret = $subscription->signing_secret_encrypted;
        $signature = $webhookService->sign($payload, $secret);

        $delivery->update([
            'attempt_count' => $delivery->attempt_count + 1,
            'last_attempt_at' => now(),
        ]);

        try {
            $response = Http::timeout(10)->withHeaders([
                'X-Platform-Signature' => $signature,
                'X-Platform-Event' => $subscription->event_type,
                'X-Platform-Delivery-Id' => $delivery->event_id,
                'X-Platform-Timestamp' => now()->toIso8601String(),
                'Content-Type' => 'application/json',
            ])->withBody($payload, 'application/json')->post($subscription->target_url);

            $delivery->update([
                'response_code' => $response->status(),
                'response_body_snippet' => substr((string) $response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $delivery->update(['status' => 'success']);
                $subscription->update(['status' => 'active']);

                return;
            }

            $this->handleFailure($delivery, $subscription);
        } catch (\Throwable $e) {
            $delivery->update([
                'response_body_snippet' => substr($e->getMessage(), 0, 500),
            ]);

            $this->handleFailure($delivery, $subscription);
        }
    }

    private function handleFailure(WebhookDelivery $delivery, $subscription): void
    {
        $consecutiveFailures = WebhookDelivery::where('subscription_id', $subscription->id)
            ->where('status', '!=', 'success')
            ->where('attempt_count', '>=', 1)
            ->orderByDesc('id')
            ->limit(5)
            ->count();

        if ($delivery->attempt_count >= $this->tries) {
            $delivery->update(['status' => 'failed']);
        }

        if ($consecutiveFailures >= 5) {
            $subscription->update(['status' => 'paused']);
        }

        if ($delivery->attempt_count < $this->tries) {
            $this->release($this->backoff[$delivery->attempt_count - 1] ?? 43200);
        } else {
            $delivery->update(['status' => 'failed']);
        }
    }
}
