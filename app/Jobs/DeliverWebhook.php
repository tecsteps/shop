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

    public int $maxExceptions = 6;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public function __construct(
        public WebhookDelivery $delivery,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $delivery = $this->delivery->fresh();
        $subscription = $delivery->subscription;

        $payloadJson = json_encode($delivery->payload_json);
        $signature = $webhookService->sign($payloadJson, $subscription->secret);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $delivery->event_type,
                    'X-Platform-Delivery-Id' => (string) $delivery->id,
                    'X-Platform-Timestamp' => (string) now()->timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($subscription->target_url);

            if ($response->successful()) {
                $delivery->update([
                    'status' => 'success',
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                    'attempt_count' => $delivery->attempt_count + 1,
                    'completed_at' => now(),
                ]);

                $subscription->update([
                    'consecutive_failures' => 0,
                ]);
            } else {
                $this->handleFailure($delivery, $subscription, $response->status(), $response->body());
            }
        } catch (\Exception $e) {
            $this->handleFailure($delivery, $subscription, null, $e->getMessage());

            throw $e;
        }
    }

    private function handleFailure(
        WebhookDelivery $delivery,
        mixed $subscription,
        ?int $responseStatus,
        ?string $responseBody,
    ): void {
        $attemptCount = $delivery->attempt_count + 1;

        $delivery->update([
            'status' => $attemptCount >= $this->tries ? 'failed' : 'pending',
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'attempt_count' => $attemptCount,
            'completed_at' => $attemptCount >= $this->tries ? now() : null,
        ]);

        $consecutiveFailures = $subscription->consecutive_failures + 1;

        $subscription->update([
            'consecutive_failures' => $consecutiveFailures,
        ]);

        if ($consecutiveFailures >= 5) {
            $subscription->update([
                'status' => 'paused',
            ]);
        }
    }
}
