<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

final class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /** @var list<int> */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly WebhookDelivery $delivery,
        public readonly string $eventType,
        public readonly array $payload,
    ) {}

    public function handle(WebhookService $webhooks): void
    {
        $delivery = $this->delivery->fresh('subscription');
        $subscriptionStatus = $delivery?->subscription?->status;
        $subscriptionStatus = $subscriptionStatus instanceof \BackedEnum ? $subscriptionStatus->value : $subscriptionStatus;
        if ($delivery === null || $delivery->subscription === null || $subscriptionStatus !== 'active') {
            return;
        }

        $body = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $secret = (string) $delivery->subscription->signing_secret_encrypted;
        $delivery->increment('attempt_count');
        $delivery->update(['last_attempt_at' => now()]);

        try {
            $response = Http::timeout(10)->withHeaders([
                'X-Platform-Signature' => $webhooks->sign($body, $secret),
                'X-Platform-Event' => $this->eventType,
                'X-Platform-Delivery-Id' => $delivery->event_id,
                'X-Platform-Timestamp' => (string) now()->timestamp,
                'Content-Type' => 'application/json',
            ])->withBody($body, 'application/json')->post($delivery->subscription->target_url);

            $delivery->update([
                'status' => $response->successful() ? 'success' : 'failed',
                'response_code' => $response->status(),
                'response_body_snippet' => mb_substr($response->body(), 0, 1000),
            ]);
            if (! $response->successful()) {
                $webhooks->recordFailure($delivery);
                $response->throw();
            }
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'response_body_snippet' => mb_substr($exception->getMessage(), 0, 1000)]);
            $webhooks->recordFailure($delivery);
            throw $exception;
        }
    }
}
