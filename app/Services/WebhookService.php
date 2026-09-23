<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WebhookService
{
    /** @param array<string, mixed> $payload */
    public function dispatch(Store $store, string $eventType, array $payload, ?DateTimeInterface $occurredAt = null): int
    {
        $timestamp = $occurredAt === null
            ? now('UTC')->timestamp
            : CarbonImmutable::instance($occurredAt)->utc()->timestamp;
        $subscriptionIds = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('event_type', $eventType)
            ->where('status', 'active')
            ->orderBy('id')
            ->pluck('id');

        foreach ($subscriptionIds as $subscriptionId) {
            DeliverWebhook::dispatch(
                (int) $subscriptionId,
                (string) Str::uuid(),
                $eventType,
                $payload,
                $timestamp,
            );
        }

        return $subscriptionIds->count();
    }

    /** @param array<string, mixed> $payload */
    public function deliver(int $subscriptionId, string $deliveryId, string $eventType, array $payload, int $timestamp): void
    {
        $subscription = WebhookSubscription::withoutGlobalScopes()->find($subscriptionId);

        if (! $subscription || $subscription->status !== 'active') {
            return;
        }

        $delivery = WebhookDelivery::query()->firstOrCreate(
            ['subscription_id' => $subscription->getKey(), 'event_id' => $deliveryId],
            ['status' => 'pending', 'attempt_count' => 1, 'last_attempt_at' => now()],
        );

        if ($delivery->status === 'success') {
            return;
        }

        if (! $delivery->wasRecentlyCreated) {
            $delivery->forceFill([
                'status' => 'pending',
                'attempt_count' => (int) $delivery->attempt_count + 1,
                'last_attempt_at' => now(),
                'response_code' => null,
                'response_body_snippet' => null,
            ])->save();
        }

        try {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $signature = hash_hmac('sha256', $body, $subscription->signing_secret_encrypted);
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withHeaders([
                    'X-Platform-Signature' => $signature,
                    'X-Platform-Event' => $eventType,
                    'X-Platform-Delivery-Id' => $deliveryId,
                    'X-Platform-Timestamp' => (string) $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($subscription->target_url);
        } catch (Throwable $exception) {
            $this->recordFailure($subscriptionId, (int) $delivery->getKey(), null, null);

            throw $exception;
        }

        if (! $response->successful()) {
            $this->recordFailure(
                $subscriptionId,
                (int) $delivery->getKey(),
                $response->status(),
                Str::limit($response->body(), 1000),
            );

            throw new RuntimeException("Webhook endpoint responded with HTTP {$response->status()}.");
        }

        DB::transaction(function () use ($delivery, $response): void {
            $delivery->forceFill([
                'status' => 'success',
                'response_code' => $response->status(),
                'response_body_snippet' => Str::limit($response->body(), 1000),
            ])->save();
        });
    }

    private function recordFailure(int $subscriptionId, int $deliveryId, ?int $responseCode, ?string $responseBodySnippet): void
    {
        DB::transaction(function () use ($subscriptionId, $deliveryId, $responseCode, $responseBodySnippet): void {
            $subscription = WebhookSubscription::withoutGlobalScopes()
                ->whereKey($subscriptionId)
                ->lockForUpdate()
                ->first();
            $delivery = WebhookDelivery::query()->whereKey($deliveryId)->first();

            if (! $subscription || ! $delivery) {
                return;
            }

            $delivery->forceFill([
                'status' => 'failed',
                'response_code' => $responseCode,
                'response_body_snippet' => $responseBodySnippet,
            ])->save();

            if ($subscription->status !== 'active') {
                return;
            }

            $consecutiveFailures = 0;
            $recentDeliveries = WebhookDelivery::query()
                ->where('subscription_id', $subscriptionId)
                ->where('last_attempt_at', '>=', $subscription->updated_at)
                ->whereIn('status', ['success', 'failed'])
                ->orderByDesc('last_attempt_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['status', 'attempt_count']);

            foreach ($recentDeliveries as $recentDelivery) {
                if ($recentDelivery->status === 'success') {
                    break;
                }

                $consecutiveFailures += max(1, (int) $recentDelivery->attempt_count);

                if ($consecutiveFailures >= 5) {
                    break;
                }
            }

            if ($consecutiveFailures >= 5) {
                $subscription->forceFill(['status' => 'paused'])->save();
                Log::warning('Webhook subscription paused after five consecutive delivery failures.', [
                    'subscription_id' => $subscriptionId,
                    'consecutive_failures' => $consecutiveFailures,
                ]);
            }
        });
    }
}
