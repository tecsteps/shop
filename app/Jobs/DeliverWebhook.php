<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function __construct(
        public WebhookDelivery $delivery,
        public string $eventType,
        public array $payload,
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $subscription = WebhookSubscription::withoutGlobalScopes()
            ->find($this->delivery->subscription_id);

        if (! $subscription || $subscription->status !== WebhookSubscriptionStatus::Active) {
            $this->delivery->update([
                'status' => WebhookDeliveryStatus::Failed,
                'last_attempt_at' => now()->toIso8601String(),
            ]);

            return;
        }

        $payloadJson = json_encode($this->payload);
        $secret = $subscription->signing_secret_encrypted;
        $signature = $webhookService->sign($payloadJson, $secret);
        $deliveryId = Str::uuid()->toString();
        $timestamp = (string) time();

        $this->delivery->update([
            'attempt_count' => $this->delivery->attempt_count + 1,
            'last_attempt_at' => now()->toIso8601String(),
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => $deliveryId,
                    'X-Platform-Timestamp' => $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($subscription->target_url);

            $this->delivery->update([
                'response_code' => $response->status(),
                'response_body_snippet' => Str::limit($response->body(), 500),
            ]);

            if ($response->successful()) {
                $this->delivery->update(['status' => WebhookDeliveryStatus::Success]);
                $this->resetConsecutiveFailures($subscription);

                return;
            }

            throw new \RuntimeException("Webhook delivery failed with status {$response->status()}");
        } catch (\Exception $e) {
            $this->incrementConsecutiveFailures($subscription);

            if ($this->delivery->attempt_count >= $this->tries) {
                $this->delivery->update(['status' => WebhookDeliveryStatus::Failed]);

                return;
            }

            throw $e;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->delivery->update([
            'status' => WebhookDeliveryStatus::Failed,
            'last_attempt_at' => now()->toIso8601String(),
        ]);

        $subscription = WebhookSubscription::withoutGlobalScopes()
            ->find($this->delivery->subscription_id);

        if ($subscription) {
            $this->incrementConsecutiveFailures($subscription);
        }
    }

    protected function resetConsecutiveFailures(WebhookSubscription $subscription): void
    {
        $consecutiveFailures = $this->countConsecutiveFailures($subscription);
        if ($consecutiveFailures > 0) {
            // The counter resets naturally since we just had a success
        }
    }

    protected function incrementConsecutiveFailures(WebhookSubscription $subscription): void
    {
        $consecutiveFailures = $this->countConsecutiveFailures($subscription);

        if ($consecutiveFailures >= 5) {
            $subscription->update(['status' => WebhookSubscriptionStatus::Paused]);
            Log::warning("Webhook subscription {$subscription->id} paused after {$consecutiveFailures} consecutive failures.");
        }
    }

    protected function countConsecutiveFailures(WebhookSubscription $subscription): int
    {
        $recentDeliveries = WebhookDelivery::where('subscription_id', $subscription->id)
            ->orderByDesc('id')
            ->limit(5)
            ->pluck('status');

        $consecutiveFailures = 0;
        foreach ($recentDeliveries as $status) {
            if ($status === WebhookDeliveryStatus::Failed) {
                $consecutiveFailures++;
            } else {
                break;
            }
        }

        return $consecutiveFailures;
    }
}
