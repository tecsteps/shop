<?php

use App\Enums\WebhookTopic;
use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeWebhookFixture(string $secret = 'secret-value'): array
{
    $store = Store::factory()->create();
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookTopic::OrderPaid->value,
        'target_url' => 'https://example.test/webhook',
        'signing_secret_encrypted' => $secret,
        'status' => 'active',
    ]);

    return [$subscription, $secret];
}

it('records a success delivery on 200 and posts the signed payload', function () {
    Http::fake(['example.test/*' => Http::response('ok', 200)]);

    [$subscription, $secret] = makeWebhookFixture();
    $eventId = (string) Str::uuid();
    $payload = ['order_id' => 42];
    $timestamp = 1700000000;

    $job = new DeliverWebhook(
        (int) $subscription->getKey(),
        $eventId,
        WebhookTopic::OrderPaid->value,
        $payload,
        $timestamp,
    );
    $job->handle();

    $delivery = WebhookDelivery::query()->where('subscription_id', $subscription->getKey())->latest('id')->first();

    expect($delivery->status)->toBe('success')
        ->and($delivery->response_code)->toBe(200);

    $expectedBody = json_encode([
        'event_id' => $eventId,
        'topic' => WebhookTopic::OrderPaid->value,
        'timestamp' => $timestamp,
        'data' => $payload,
    ]);
    $expectedSignature = hash_hmac('sha256', (string) $expectedBody, $secret);

    Http::assertSent(function ($request) use ($expectedBody, $expectedSignature, $eventId, $subscription): bool {
        return $request->url() === $subscription->target_url
            && $request->header('X-Shop-Signature')[0] === $expectedSignature
            && $request->header('X-Shop-Event-Id')[0] === $eventId
            && $request->body() === $expectedBody;
    });
});

it('records failure and schedules next retry on 500', function () {
    Http::fake(['example.test/*' => Http::response('boom', 500)]);

    [$subscription] = makeWebhookFixture();

    $job = new DeliverWebhook(
        (int) $subscription->getKey(),
        (string) Str::uuid(),
        WebhookTopic::OrderPaid->value,
        ['k' => 'v'],
        now()->timestamp,
    );

    try {
        $job->handle();
    } catch (Throwable) {
        // expected: job throws so Laravel can retry
    }

    $delivery = WebhookDelivery::query()->where('subscription_id', $subscription->getKey())->latest('id')->first();

    expect($delivery->status)->toBe('pending')
        ->and($delivery->response_code)->toBe(500)
        ->and($delivery->next_retry_at)->not->toBeNull()
        ->and($subscription->refresh()->consecutive_failures)->toBe(1);
});

it('pauses the subscription after 5 consecutive failures', function () {
    Http::fake(['example.test/*' => Http::response('boom', 500)]);

    [$subscription] = makeWebhookFixture();
    $subscription->consecutive_failures = 4;
    $subscription->save();

    $job = new DeliverWebhook(
        (int) $subscription->getKey(),
        (string) Str::uuid(),
        WebhookTopic::OrderPaid->value,
        [],
        now()->timestamp,
    );

    try {
        $job->handle();
    } catch (Throwable) {
    }

    expect($subscription->refresh()->status)->toBe('paused')
        ->and($subscription->consecutive_failures)->toBe(5);
});

it('skips delivery when subscription is not active', function () {
    Http::fake();

    [$subscription] = makeWebhookFixture();
    $subscription->status = 'paused';
    $subscription->save();

    $job = new DeliverWebhook(
        (int) $subscription->getKey(),
        (string) Str::uuid(),
        WebhookTopic::OrderPaid->value,
        [],
        now()->timestamp,
    );
    $job->handle();

    expect(WebhookDelivery::query()->count())->toBe(0);
    Http::assertNothingSent();
});
