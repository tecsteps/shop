<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
});

it('queues a DeliverWebhook job for each active subscription', function (): void {
    Bus::fake();

    WebhookSubscription::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
    ]);
    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'status' => WebhookSubscriptionStatus::Disabled,
    ]);
    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.paid',
    ]);

    $count = app(WebhookService::class)->dispatchEvent($this->store->id, 'order.created', ['hi' => 'there']);

    expect($count)->toBe(2);
    Bus::assertDispatchedTimes(DeliverWebhook::class, 2);
});

it('delivers a webhook successfully and records response', function (): void {
    Http::fake([
        '*' => Http::response('ok', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'target_url' => 'https://example.com/hooks/1',
    ]);

    (new DeliverWebhook($subscription->id, 'order.created', ['k' => 'v'], 'evt-1'))->handle(app(WebhookService::class));

    $delivery = WebhookDelivery::query()->where('subscription_id', $subscription->id)->first();
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success);
    expect($delivery->response_code)->toBe(200);
    expect($delivery->attempt_count)->toBe(1);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Event', 'order.created')
            && $request->hasHeader('X-Platform-Delivery-Id', 'evt-1');
    });
});

it('increments consecutive_failures and pauses after 5 failures', function (): void {
    Http::fake([
        '*' => Http::response('down', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'consecutive_failures' => 4,
    ]);

    try {
        (new DeliverWebhook($subscription->id, 'order.created', [], 'evt-fail'))->handle(app(WebhookService::class));
    } catch (\Throwable) {
    }

    $subscription->refresh();
    expect($subscription->consecutive_failures)->toBe(5);
    expect($subscription->status)->toBe(WebhookSubscriptionStatus::Paused);
});

it('resets consecutive_failures on success', function (): void {
    Http::fake([
        '*' => Http::response('ok', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'consecutive_failures' => 3,
    ]);

    (new DeliverWebhook($subscription->id, 'order.created', [], 'evt-recover'))->handle(app(WebhookService::class));

    expect($subscription->fresh()->consecutive_failures)->toBe(0);
});

it('uses configured backoff schedule', function (): void {
    $job = new DeliverWebhook(1, 'order.created', [], 'evt');
    expect($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);
    expect($job->tries)->toBe(6);
});

it('marks delivery as failed via failed() hook', function (): void {
    $subscription = WebhookSubscription::factory()->create(['store_id' => $this->store->id]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'event_id' => 'evt-boom',
        'status' => WebhookDeliveryStatus::Pending,
    ]);

    (new DeliverWebhook($subscription->id, 'order.created', [], 'evt-boom'))->failed(new \Exception('boom'));

    expect($delivery->fresh()->status)->toBe(WebhookDeliveryStatus::Failed);
});
