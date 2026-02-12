<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    /** @var list<int> */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public int $tries = 5;

    public function __construct(
        public WebhookDelivery $delivery,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $delivery = $this->delivery;
        $subscription = $delivery->subscription;

        if (! $subscription || ! $subscription->is_active) {
            return;
        }

        $payload = $delivery->payload_json;
        $timestamp = now()->toIso8601String();

        $headers = [
            'Content-Type' => 'application/json',
            'X-Platform-Event' => $delivery->event_type,
            'X-Platform-Delivery-Id' => (string) $delivery->id,
            'X-Platform-Timestamp' => $timestamp,
        ];

        if ($subscription->secret) {
            $headers['X-Platform-Signature'] = $webhookService->sign($payload, $subscription->secret);
        }

        $delivery->increment('attempts');

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->withBody($payload, 'application/json')
                ->post($subscription->url);

            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 2000),
            ]);

            if ($response->successful()) {
                $delivery->update(['delivered_at' => now()]);
                $subscription->update(['failure_count' => 0]);
            } else {
                $this->handleFailure($delivery, $subscription);
            }
        } catch (Throwable $e) {
            $delivery->update([
                'response_body' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            $this->handleFailure($delivery, $subscription);
        }
    }

    private function handleFailure(WebhookDelivery $delivery, mixed $subscription): void
    {
        $subscription->increment('failure_count');

        if ($subscription->failure_count >= 5) {
            $subscription->update(['is_active' => false]);
            $delivery->update(['failed_at' => now()]);

            return;
        }

        $retryIndex = min($delivery->attempts - 1, count($this->backoff) - 1);
        $retryDelay = $this->backoff[$retryIndex];

        $delivery->update([
            'next_retry_at' => now()->addSeconds($retryDelay),
        ]);
    }
}
