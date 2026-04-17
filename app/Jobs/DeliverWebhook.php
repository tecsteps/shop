<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly WebhookSubscription $subscription,
        public readonly string $eventType,
        public readonly array $payload,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $subscription = $this->subscription->fresh() ?? $this->subscription;

        if ($subscription->status !== 'active') {
            return;
        }

        $body = json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        $signature = $webhookService->sign($body, (string) $subscription->secret);
        $deliveryId = (string) Str::uuid();
        $timestamp = (string) now()->getTimestamp();

        $delivery = WebhookDelivery::create([
            'subscription_id' => $subscription->id,
            'event_type' => $this->eventType,
            'payload_json' => $this->payload,
            'attempts' => $this->attempts(),
        ]);

        $response = null;

        try {
            /** @var Response $response */
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => $deliveryId,
                    'X-Platform-Timestamp' => $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post((string) $subscription->url);
        } catch (Throwable $exception) {
            $delivery->update([
                'response_body' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            $this->recordFailure($subscription);
            throw $exception;
        }

        $delivery->update([
            'response_status' => $response->status(),
            'response_body' => mb_substr((string) $response->body(), 0, 2000),
            'delivered_at' => now(),
        ]);

        if ($response->successful()) {
            return;
        }

        $this->recordFailure($subscription);
        throw new \RuntimeException('Webhook responded with non-2xx status: '.$response->status());
    }

    private function recordFailure(WebhookSubscription $subscription): void
    {
        $subscription->increment('failed_count');
        $subscription->refresh();

        if ($subscription->failed_count >= 5 && $subscription->status === 'active') {
            $subscription->update(['status' => 'paused']);
        }
    }
}
