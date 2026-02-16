<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    /** @var array<int> */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public int $tries = 5;

    public function __construct(
        public WebhookSubscription $subscription,
        public string $eventType,
        public array $payload,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $jsonPayload = json_encode($this->payload);
        $signature = $webhookService->sign($jsonPayload, $this->subscription->secret);
        $deliveryId = Str::uuid()->toString();

        $delivery = WebhookDelivery::create([
            'subscription_id' => $this->subscription->id,
            'event_type' => $this->eventType,
            'payload_json' => $this->payload,
            'status' => 'pending',
        ]);

        try {
            $response = Http::timeout(30)->withHeaders([
                'X-Platform-Signature' => $signature,
                'X-Platform-Event' => $this->eventType,
                'X-Platform-Delivery-Id' => $deliveryId,
                'X-Platform-Timestamp' => now()->toIso8601String(),
                'Content-Type' => 'application/json',
            ])->withBody($jsonPayload, 'application/json')->post($this->subscription->target_url);

            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'attempt_count' => $this->attempts(),
                'status' => $response->successful() ? 'delivered' : 'failed',
                'delivered_at' => $response->successful() ? now() : null,
            ]);

            if ($response->successful()) {
                $this->subscription->update(['consecutive_failures' => 0]);
            } else {
                $this->recordFailure($delivery);
            }
        } catch (\Throwable $e) {
            $delivery->update([
                'response_body' => $e->getMessage(),
                'attempt_count' => $this->attempts(),
                'status' => 'failed',
            ]);

            $this->recordFailure($delivery);

            throw $e;
        }
    }

    protected function recordFailure(WebhookDelivery $delivery): void
    {
        $failures = $this->subscription->consecutive_failures + 1;
        $updateData = ['consecutive_failures' => $failures];

        if ($failures >= 5) {
            $updateData['status'] = 'paused';
        }

        $this->subscription->update($updateData);
    }
}
