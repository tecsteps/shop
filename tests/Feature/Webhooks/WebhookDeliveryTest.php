<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->webhookService = app(WebhookService::class);
});

it('delivers a webhook to a subscribed URL', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'signing_secret_encrypted' => 'test-secret',
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'status' => WebhookDeliveryStatus::Pending,
        'attempt_count' => 0,
    ]);

    $payload = ['event' => 'order.created', 'data' => ['id' => 1]];

    $job = new DeliverWebhook($delivery, $payload);
    $job->handle($this->webhookService);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhook'
            && $request->hasHeader('X-Platform-Signature')
            && $request->hasHeader('X-Platform-Event');
    });

    $delivery->refresh();
    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(200);
});

it('signs the payload with HMAC', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $secret = 'my-signing-secret';
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'signing_secret_encrypted' => $secret,
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'status' => WebhookDeliveryStatus::Pending,
        'attempt_count' => 0,
    ]);

    $payload = ['event' => 'order.created'];
    $payloadJson = json_encode($payload);
    $expectedSignature = hash_hmac('sha256', $payloadJson, $secret);

    $job = new DeliverWebhook($delivery, $payload);
    $job->handle($this->webhookService);

    Http::assertSent(function ($request) use ($expectedSignature) {
        return $request->header('X-Platform-Signature')[0] === $expectedSignature;
    });
});

it('retries failed deliveries with exponential backoff', function () {
    Http::fake([
        '*' => Http::response('Server Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'signing_secret_encrypted' => 'test-secret',
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'status' => WebhookDeliveryStatus::Pending,
        'attempt_count' => 0,
    ]);

    $job = new DeliverWebhook($delivery, ['event' => 'order.created']);
    $job->handle($this->webhookService);

    $delivery->refresh();

    expect($delivery->attempt_count)->toBe(1)
        ->and($delivery->response_code)->toBe(500);
});

it('marks delivery as failed after max retries', function () {
    Http::fake([
        '*' => Http::response('Server Error', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'signing_secret_encrypted' => 'test-secret',
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    // Start with attempt_count = 5, so after increment it will be 6 (>= tries)
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'status' => WebhookDeliveryStatus::Pending,
        'attempt_count' => 5,
    ]);

    $job = new DeliverWebhook($delivery, ['event' => 'order.created']);
    $job->handle($this->webhookService);

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->attempt_count)->toBe(6);
});

it('pauses subscription after circuit breaker threshold', function () {
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/webhook',
        'signing_secret_encrypted' => 'test-secret',
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    // Create 4 consecutive failed deliveries (need 5 total for circuit breaker)
    for ($i = 0; $i < 4; $i++) {
        WebhookDelivery::factory()->failed()->create([
            'subscription_id' => $subscription->id,
            'last_attempt_at' => now()->subMinutes(5 - $i),
        ]);
    }

    Http::fake([
        '*' => Http::response('Server Error', 500),
    ]);

    // This will be the 5th failed delivery
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->id,
        'status' => WebhookDeliveryStatus::Pending,
        'attempt_count' => 5,
    ]);

    $job = new DeliverWebhook($delivery, ['event' => 'order.created']);
    $job->handle($this->webhookService);

    $subscription->refresh();
    expect($subscription->status)->toBe(WebhookSubscriptionStatus::Paused);
});
