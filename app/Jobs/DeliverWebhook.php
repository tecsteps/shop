<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120, 300, 900, 3600, 7200];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $eventId,
        public readonly string $topic,
        public readonly array $payload,
        public readonly int $timestamp,
    ) {}

    public function handle(): void
    {
        $subscription = WebhookSubscription::query()->withoutGlobalScopes()->find($this->subscriptionId);

        if ($subscription === null || $subscription->status !== 'active') {
            return;
        }

        $body = json_encode([
            'event_id' => $this->eventId,
            'topic' => $this->topic,
            'timestamp' => $this->timestamp,
            'data' => $this->payload,
        ]);

        $signature = hash_hmac('sha256', (string) $body, (string) $subscription->signing_secret_encrypted);

        $delivery = WebhookDelivery::query()->create([
            'subscription_id' => $subscription->getKey(),
            'event_id' => $this->eventId,
            'attempt_count' => max(1, $this->attempts()),
            'status' => 'pending',
            'last_attempt_at' => now(),
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Shop-Topic' => $this->topic,
                'X-Shop-Signature' => $signature,
                'X-Shop-Event-Id' => $this->eventId,
                'X-Shop-Timestamp' => (string) $this->timestamp,
            ])->withBody($body, 'application/json')->post($subscription->target_url);

            $delivery->response_code = $response->status();
            $delivery->response_body_snippet = substr((string) $response->body(), 0, 500);

            if ($response->successful()) {
                $delivery->status = 'success';
                $delivery->next_retry_at = null;
                $delivery->save();

                $subscription->consecutive_failures = 0;
                $subscription->save();

                return;
            }

            $this->recordFailure($subscription, $delivery);
            throw new \RuntimeException("Webhook delivery failed with HTTP {$response->status()}.");
        } catch (\RuntimeException $e) {
            // Already recorded as failure above; rethrow so Laravel retries.
            throw $e;
        } catch (Throwable $e) {
            $delivery->response_body_snippet = substr($e->getMessage(), 0, 500);
            $this->recordFailure($subscription, $delivery);

            Log::warning('webhook.delivery_failed', [
                'subscription_id' => $subscription->getKey(),
                'topic' => $this->topic,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $delivery = WebhookDelivery::query()
            ->where('subscription_id', $this->subscriptionId)
            ->where('event_id', $this->eventId)
            ->latest('id')
            ->first();

        if ($delivery !== null) {
            $delivery->status = 'failed';
            $delivery->next_retry_at = null;
            $delivery->save();
        }
    }

    protected function recordFailure(WebhookSubscription $subscription, WebhookDelivery $delivery): void
    {
        $delivery->status = 'pending';
        $delivery->next_retry_at = $this->nextRetryAt();
        $delivery->save();

        $subscription->consecutive_failures = (int) $subscription->consecutive_failures + 1;

        if ($subscription->consecutive_failures >= 5) {
            $subscription->status = 'paused';
        }

        $subscription->save();
    }

    protected function nextRetryAt(): ?\Carbon\CarbonInterface
    {
        $attempt = max(1, $this->attempts());
        $delay = $this->backoff[$attempt - 1] ?? null;

        if ($delay === null) {
            return null;
        }

        return now()->addSeconds((int) $delay);
    }
}
