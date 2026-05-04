<?php

namespace App\Services;

use App\Enums\AppInstallationStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEventType;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\App as AppModel;
use App\Models\AppInstallation;
use App\Models\OauthToken;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WebhookService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Store $store, string $eventType, array $payload): void
    {
        $event = WebhookEventType::tryFrom($eventType);

        if (! $event instanceof WebhookEventType) {
            throw new InvalidArgumentException("Unsupported webhook event type [{$eventType}].");
        }

        WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('event_type', $event->value)
            ->where('status', WebhookSubscriptionStatus::Active->value)
            ->get()
            ->each(function (WebhookSubscription $subscription) use ($store, $event, $payload): void {
                $delivery = WebhookDelivery::query()->create([
                    'subscription_id' => $subscription->getKey(),
                    'event_id' => (string) Str::uuid(),
                    'attempt_count' => 1,
                    'status' => WebhookDeliveryStatus::Pending,
                ]);

                DeliverWebhook::dispatch(
                    $delivery->getKey(),
                    $event->value,
                    $this->envelope($store, $event, $delivery->event_id, $payload),
                )->onConnection('database')->onQueue('webhooks');
            });
    }

    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function verify(string $payload, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($payload, $secret), $signature);
    }

    /**
     * @param  list<string>  $abilities
     * @return array{token: OauthToken, plain_text: string}
     */
    public function createApiToken(Store $store, string $name, array $abilities): array
    {
        $app = AppModel::query()->firstOrCreate(
            ['name' => 'Admin API'],
            [
                'status' => 'active',
                'created_at' => now(),
            ],
        );

        $installation = AppInstallation::withoutGlobalScopes()->firstOrCreate(
            [
                'store_id' => $store->getKey(),
                'app_id' => $app->getKey(),
            ],
            [
                'scopes_json' => $abilities,
                'status' => AppInstallationStatus::Active,
                'installed_at' => now(),
            ],
        );

        $plainText = 'shop_'.Str::random(48);
        $token = OauthToken::query()->create([
            'installation_id' => $installation->getKey(),
            'name' => $name,
            'access_token_hash' => hash('sha256', $plainText),
            'refresh_token_hash' => null,
            'abilities_json' => $abilities,
            'expires_at' => now()->addYear(),
            'created_at' => now(),
        ]);

        return [
            'token' => $token,
            'plain_text' => $plainText,
        ];
    }

    public function createSigningSecret(): string
    {
        return 'whsec_'.Str::random(40);
    }

    public function recordSuccess(WebhookDelivery $delivery, int $attemptCount, int $responseCode, string $responseBody): void
    {
        $delivery->forceFill([
            'attempt_count' => $attemptCount,
            'status' => WebhookDeliveryStatus::Success,
            'last_attempt_at' => now(),
            'response_code' => $responseCode,
            'response_body_snippet' => Str::limit($responseBody, 1000, ''),
        ])->save();
    }

    public function recordFailure(WebhookDelivery $delivery, int $attemptCount, ?int $responseCode, ?string $responseBody): void
    {
        $delivery->forceFill([
            'attempt_count' => $attemptCount,
            'status' => WebhookDeliveryStatus::Failed,
            'last_attempt_at' => now(),
            'response_code' => $responseCode,
            'response_body_snippet' => Str::limit((string) $responseBody, 1000, ''),
        ])->save();

        $subscription = $delivery->subscription()->first();

        if ($subscription instanceof WebhookSubscription && $this->hasFiveConsecutiveFailures($subscription)) {
            $subscription->forceFill([
                'status' => WebhookSubscriptionStatus::Paused,
            ])->save();
        }
    }

    public function hasFiveConsecutiveFailures(WebhookSubscription $subscription): bool
    {
        $recentStatuses = $subscription->deliveries()
            ->latest('id')
            ->limit(5)
            ->pluck('status');

        return $recentStatuses->count() === 5
            && $recentStatuses->every(fn (WebhookDeliveryStatus|string $status): bool => $status === WebhookDeliveryStatus::Failed || $status === WebhookDeliveryStatus::Failed->value);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function envelope(Store $store, WebhookEventType $event, string $eventId, array $payload): array
    {
        return [
            'id' => $eventId,
            'api_version' => '2026-05',
            'event_type' => $event->value,
            'store_id' => $store->getKey(),
            'occurred_at' => now()->toISOString(),
            'data' => $payload,
        ];
    }
}
