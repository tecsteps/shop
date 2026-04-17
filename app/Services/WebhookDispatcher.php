<?php

namespace App\Services;

use App\Enums\WebhookTopic;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(WebhookTopic $topic, int $storeId, array $payload): int
    {
        $subscriptions = WebhookSubscription::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('event_type', $topic->value)
            ->where('status', 'active')
            ->get();

        $eventId = (string) Str::uuid();
        $timestamp = now()->timestamp;

        foreach ($subscriptions as $subscription) {
            DeliverWebhook::dispatch(
                $subscription->getKey(),
                $eventId,
                $topic->value,
                $payload,
                $timestamp,
            );
        }

        return $subscriptions->count();
    }

    /**
     * Convenience dispatcher that resolves store from the payload or current_store binding.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatchForTopic(WebhookTopic $topic, ?int $storeId, array $payload): int
    {
        if ($storeId === null) {
            if (! app()->bound('current_store')) {
                return 0;
            }

            $store = app('current_store');

            if (! $store instanceof Store) {
                return 0;
            }

            $storeId = (int) $store->getKey();
        }

        return $this->dispatch($topic, $storeId, $payload);
    }
}
