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

    public $tries = 6;

    public $backoff = [60, 300, 1800, 7200, 43200];

    public function __construct(
        public WebhookSubscription $subscription,
        public string $eventType,
        public array $payload,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $eventId = (string) Str::uuid();
        $secret = $this->subscription->signing_secret_encrypted;

        $response = Http::timeout(10)->withHeaders([
            'X-Platform-Signature' => $webhookService->sign(json_encode($this->payload), $secret),
            'X-Platform-Event' => $this->eventType,
            'X-Platform-Delivery-Id' => $eventId,
            'X-Platform-Timestamp' => (string) time(),
        ])->post($this->subscription->target_url, $this->payload);

        WebhookDelivery::create([
            'subscription_id' => $this->subscription->id,
            'event_id' => $eventId,
            'attempt_count' => $this->attempts(),
            'status' => $response->successful() ? 'success' : 'failed',
            'last_attempt_at' => now(),
            'response_code' => $response->status(),
            'response_body_snippet' => substr($response->body(), 0, 200),
        ]);
    }
}
