<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 6;

    public function __construct(public WebhookDelivery $delivery) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function handle(WebhookService $webhooks): void
    {
        $this->delivery->load('subscription');
        $payload = json_encode($this->delivery->payload, JSON_THROW_ON_ERROR);
        $subscription = $this->delivery->subscription;
        $response = Http::withHeaders([
            'X-Platform-Signature' => $webhooks->sign($payload, $subscription->secret_encrypted),
            'X-Platform-Event' => $this->delivery->event,
            'X-Platform-Delivery-Id' => (string) $this->delivery->getKey(),
            'X-Platform-Timestamp' => (string) now()->timestamp,
        ])->timeout(10)->post($subscription->target_url, $this->delivery->payload);

        $this->delivery->increment('attempts');
        $this->delivery->update(['response_status' => $response->status(), 'response_body' => mb_substr($response->body(), 0, 10000)]);

        if ($response->successful()) {
            $subscription->update(['consecutive_failures' => 0]);
            $this->delivery->update(['status' => 'delivered', 'delivered_at' => now(), 'next_attempt_at' => null]);

            return;
        }

        $subscription->increment('consecutive_failures');

        if ($subscription->fresh()->consecutive_failures >= 5) {
            $subscription->update(['status' => 'paused']);
        }

        throw new \RuntimeException('Webhook delivery failed with HTTP '.$response->status());
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->update(['status' => 'failed', 'next_attempt_at' => null]);
    }
}
