<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public function __construct(
        public int $subscriptionId,
        public string $eventType,
        public array $payload,
        public string $eventId,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 43200];
    }

    public function handle(WebhookService $service): void
    {
        $subscription = WebhookSubscription::query()->find($this->subscriptionId);

        if (! $subscription || $subscription->status !== WebhookSubscriptionStatus::Active) {
            return;
        }

        $delivery = WebhookDelivery::query()->firstOrCreate(
            ['subscription_id' => $subscription->id, 'event_id' => $this->eventId],
            ['attempt_count' => 0, 'status' => WebhookDeliveryStatus::Pending]
        );

        $delivery->attempt_count++;
        $delivery->last_attempt_at = now();

        $body = json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $secret = $service->decryptSecret($subscription);
        $signature = $service->sign($body, $secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $this->eventType,
                    'X-Platform-Delivery-Id' => $this->eventId,
                    'X-Platform-Timestamp' => (string) now()->timestamp,
                ])
                ->send('POST', $subscription->target_url, [
                    'body' => $body,
                ]);

            $delivery->response_code = $response->status();
            $delivery->response_body_snippet = mb_substr((string) $response->body(), 0, 500);

            if ($response->successful()) {
                $delivery->status = WebhookDeliveryStatus::Success;
                $delivery->save();
                if ($subscription->consecutive_failures > 0) {
                    $subscription->consecutive_failures = 0;
                    $subscription->save();
                }

                return;
            }

            $this->recordFailure($subscription, $delivery);
            throw new \RuntimeException("Webhook delivery failed with status {$response->status()}");
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            $delivery->response_body_snippet = mb_substr($e->getMessage(), 0, 500);
            $this->recordFailure($subscription, $delivery);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $delivery = WebhookDelivery::query()
            ->where('subscription_id', $this->subscriptionId)
            ->where('event_id', $this->eventId)
            ->first();

        if ($delivery) {
            $delivery->status = WebhookDeliveryStatus::Failed;
            $delivery->save();
        }
    }

    protected function recordFailure(WebhookSubscription $subscription, WebhookDelivery $delivery): void
    {
        $delivery->status = WebhookDeliveryStatus::Pending;
        $delivery->save();

        $subscription->consecutive_failures = $subscription->consecutive_failures + 1;
        if ($subscription->consecutive_failures >= 5) {
            $subscription->status = WebhookSubscriptionStatus::Paused;
        }
        $subscription->save();
    }
}
