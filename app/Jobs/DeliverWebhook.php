<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    public function __construct(
        public WebhookSubscription $subscription,
        public string $eventType,
        /** @var array<string, mixed> */
        public array $payload,
    ) {}

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function handle(): void
    {
        $jsonPayload = json_encode($this->payload);
        $timestamp = (string) time();
        $signatureData = "{$timestamp}.{$jsonPayload}";
        $secret = $this->subscription->signing_secret_encrypted;
        $signature = hash_hmac('sha256', $signatureData, $secret);

        $delivery = WebhookDelivery::create([
            'subscription_id' => $this->subscription->id,
            'event_type' => $this->eventType,
            'payload_json' => $this->payload,
            'attempts' => $this->attempts(),
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Timestamp' => $timestamp,
                    'X-Platform-Event' => $this->eventType,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($this->subscription->target_url);

            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => substr((string) $response->body(), 0, 10000),
                'delivered_at' => $response->successful() ? now() : null,
                'attempts' => $this->attempts(),
            ]);

            if ($response->successful()) {
                $this->subscription->update([
                    'consecutive_failures' => 0,
                ]);
            } else {
                $this->handleFailure($delivery);
            }
        } catch (Throwable $e) {
            $delivery->update([
                'response_status' => null,
                'response_body' => substr($e->getMessage(), 0, 10000),
                'attempts' => $this->attempts(),
            ]);

            $this->handleFailure($delivery);

            throw $e;
        }
    }

    /**
     * Handle a failed delivery attempt.
     */
    private function handleFailure(WebhookDelivery $delivery): void
    {
        $failures = $this->subscription->consecutive_failures + 1;

        $updateData = [
            'consecutive_failures' => $failures,
        ];

        // Circuit breaker: pause after 5 consecutive failures
        if ($failures >= 5) {
            $updateData['is_active'] = false;
            $updateData['paused_at'] = now();
        }

        $this->subscription->update($updateData);
    }
}
