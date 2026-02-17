<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public function __construct(
        public int $deliveryId,
        public array $payload,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $delivery = WebhookDelivery::findOrFail($this->deliveryId);
        $subscription = $delivery->subscription;

        $jsonPayload = json_encode($this->payload);
        $signature = $webhookService->sign($jsonPayload, $subscription->signing_secret_encrypted);

        $delivery->update([
            'attempt_count' => $delivery->attempt_count + 1,
            'last_attempt_at' => now()->toIso8601String(),
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $subscription->event_type,
                    'X-Platform-Delivery-Id' => $delivery->event_id,
                    'X-Platform-Timestamp' => now()->toIso8601String(),
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($subscription->target_url);

            $delivery->update([
                'response_code' => $response->status(),
                'response_body_snippet' => mb_substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $delivery->update(['status' => WebhookDeliveryStatus::Success]);

                return;
            }

            $this->handleFailure($delivery, $subscription);
        } catch (\Exception $e) {
            $delivery->update([
                'response_body_snippet' => mb_substr($e->getMessage(), 0, 500),
            ]);

            $this->handleFailure($delivery, $subscription);
        }
    }

    private function handleFailure(WebhookDelivery $delivery, $subscription): void
    {
        if ($delivery->attempt_count >= $this->tries) {
            $delivery->update(['status' => WebhookDeliveryStatus::Failed]);
        }

        $consecutiveFailures = $subscription->deliveries()
            ->latest('id')
            ->take(5)
            ->get()
            ->every(fn ($d) => $d->status === WebhookDeliveryStatus::Failed);

        if ($consecutiveFailures) {
            $subscription->update(['status' => WebhookSubscriptionStatus::Paused]);
        }

        if ($delivery->attempt_count < $this->tries) {
            $this->release($this->backoff[$delivery->attempt_count - 1] ?? 43200);
        }
    }
}
