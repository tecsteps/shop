<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $eventType,
        public readonly array $payload,
        public readonly ?string $eventId = null,
        public readonly ?int $timestamp = null,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function handle(WebhookService $webhookService): void
    {
        $subscription = WebhookSubscription::query()->find($this->subscriptionId);

        if (! $subscription || $subscription->status !== 'active') {
            return;
        }

        $payloadJson = json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payloadJson === false) {
            throw new RuntimeException('Failed to encode webhook payload.');
        }

        $eventId = $this->eventId ?? (string) Str::uuid();
        $deliveryId = (string) Str::uuid();
        $timestamp = $this->timestamp ?? now()->timestamp;
        $secret = $this->decryptSecret($subscription->signing_secret_encrypted);
        $signature = $webhookService->sign($payloadJson, $secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => $deliveryId,
                    'X-Platform-Timestamp' => (string) $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($subscription->target_url);

            $status = $response->successful() ? 'success' : 'failed';

            $this->recordDelivery(
                subscriptionId: $subscription->id,
                eventId: $eventId,
                status: $status,
                responseCode: $response->status(),
                responseBodySnippet: Str::limit($response->body(), 1000, ''),
            );

            if ($status === 'failed') {
                $webhookService->pauseIfConsecutiveFailures($subscription);

                if ($this->attempts() < $this->tries) {
                    throw new RuntimeException(
                        'Webhook delivery failed with HTTP status '.$response->status(),
                    );
                }
            }
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            $this->recordDelivery(
                subscriptionId: $subscription->id,
                eventId: $eventId,
                status: 'failed',
                responseCode: null,
                responseBodySnippet: Str::limit($exception->getMessage(), 1000, ''),
            );

            $webhookService->pauseIfConsecutiveFailures($subscription);

            if ($this->attempts() < $this->tries) {
                throw $exception;
            }
        }
    }

    private function decryptSecret(string $encryptedSecret): string
    {
        try {
            return Crypt::decryptString($encryptedSecret);
        } catch (Throwable) {
            return $encryptedSecret;
        }
    }

    private function recordDelivery(
        int $subscriptionId,
        string $eventId,
        string $status,
        ?int $responseCode,
        ?string $responseBodySnippet,
    ): void {
        WebhookDelivery::query()->create([
            'subscription_id' => $subscriptionId,
            'event_id' => $eventId,
            'attempt_count' => $this->attempts(),
            'status' => $status,
            'last_attempt_at' => now(),
            'response_code' => $responseCode,
            'response_body_snippet' => $responseBodySnippet,
        ]);
    }
}
