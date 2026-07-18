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

    public int $tries = 6;

    /** @var list<int> */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $subscriptionId,
        public string $eventType,
        public array $payload,
    ) {}

    public function handle(WebhookService $webhooks): void
    {
        $subscription = WebhookSubscription::query()->findOrFail($this->subscriptionId);
        $body = json_encode($this->payload, JSON_THROW_ON_ERROR);
        $delivery = WebhookDelivery::query()->create([
            'subscription_id' => $subscription->id,
            'event_type' => $this->eventType,
            'payload_json' => $this->payload,
            'attempt' => $this->attempts(),
            'status' => 'pending',
        ]);

        $response = Http::timeout(10)
            ->withHeaders([
                'X-Platform-Signature' => $webhooks->sign($body, $subscription->secret),
                'X-Platform-Event' => $this->eventType,
                'X-Platform-Delivery-Id' => (string) $delivery->id,
                'X-Platform-Timestamp' => (string) now()->timestamp,
            ])
            ->withBody($body, 'application/json')
            ->post($subscription->target_url);

        $delivery->update([
            'response_status' => $response->status(),
            'response_body' => Str::limit($response->body(), 2000),
            'status' => $response->successful() ? 'delivered' : 'failed',
            'delivered_at' => $response->successful() ? now() : null,
        ]);

        if ($response->successful()) {
            $subscription->update(['consecutive_failures' => 0]);

            return;
        }

        $failures = $subscription->consecutive_failures + 1;
        $subscription->update([
            'consecutive_failures' => $failures,
            'status' => $failures >= 5 ? 'paused' : $subscription->status,
        ]);

        $response->throw();
    }
}
