<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /**
     * Backoff intervals in seconds: 1 min, 5 min, 30 min, 2 h, 12 h.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function __construct(public WebhookDelivery $delivery) {}

    public function handle(WebhookService $webhookService): void
    {
        $subscription = $this->delivery->subscription;

        if (! $subscription || $subscription->status !== 'active') {
            return;
        }

        $payloadJson = json_encode($this->delivery->payload_json);
        $signature = $webhookService->sign($payloadJson, $subscription->secret);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $this->delivery->event_type,
                    'X-Platform-Delivery-Id' => (string) $this->delivery->id,
                    'X-Platform-Timestamp' => now()->toIso8601String(),
                    'Content-Type' => 'application/json',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($subscription->target_url);

            $this->delivery->refresh();
            $this->delivery->update([
                'response_status' => $response->status(),
                'response_body' => Str::limit($response->body(), 1000),
                'attempt_count' => $this->delivery->attempt_count + 1,
                'status' => $response->successful() ? 'success' : 'failed',
                'delivered_at' => $response->successful() ? now()->toIso8601String() : null,
            ]);

            if ($response->successful()) {
                $subscription->update(['consecutive_failures' => 0]);
            } else {
                $this->handleFailure($subscription);
            }
        } catch (\Throwable $e) {
            $this->delivery->refresh();
            $this->delivery->update([
                'response_status' => null,
                'response_body' => Str::limit($e->getMessage(), 1000),
                'attempt_count' => $this->delivery->attempt_count + 1,
                'status' => 'failed',
            ]);

            $this->handleFailure($subscription);

            throw $e;
        }
    }

    private function handleFailure(\App\Models\WebhookSubscription $subscription): void
    {
        $failures = $subscription->consecutive_failures + 1;
        $update = ['consecutive_failures' => $failures];

        if ($failures >= 5) {
            $update['status'] = 'paused';
        }

        $subscription->update($update);
    }
}
