<?php

use App\Jobs\DeliverWebhook;
use App\Models\Store;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

it('records successful webhook deliveries with expected signature headers', function () {
    $store = Store::factory()->create();

    $subscription = WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://hooks.example.test/orders',
        'signing_secret_encrypted' => 'test-secret',
        'status' => 'active',
    ]);

    Http::fake([
        'https://hooks.example.test/orders' => Http::response('ok', 200),
    ]);

    $job = new DeliverWebhook(
        subscriptionId: $subscription->id,
        eventType: 'order.created',
        payload: ['id' => 55],
        eventId: 'evt-123',
        timestamp: 1_700_000_000,
    );

    $service = app(WebhookService::class);
    $job->handle($service);

    $payloadJson = json_encode(['id' => 55], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $expectedSignature = $service->sign((string) $payloadJson, 'test-secret');

    Http::assertSent(function (HttpRequest $request) use ($expectedSignature, $payloadJson): bool {
        return $request->url() === 'https://hooks.example.test/orders'
            && (($request->header('X-Platform-Signature')[0] ?? null) === $expectedSignature)
            && (($request->header('X-Platform-Event')[0] ?? null) === 'order.created')
            && $request->body() === $payloadJson;
    });

    $this->assertDatabaseHas('webhook_deliveries', [
        'subscription_id' => $subscription->id,
        'event_id' => 'evt-123',
        'status' => 'success',
        'response_code' => 200,
    ]);
});

it('records failed webhook deliveries when remote endpoint fails', function () {
    $store = Store::factory()->create();

    $subscription = WebhookSubscription::query()->create([
        'store_id' => $store->id,
        'event_type' => 'order.created',
        'target_url' => 'https://hooks.example.test/fail',
        'signing_secret_encrypted' => 'test-secret',
        'status' => 'active',
    ]);

    Http::fake([
        'https://hooks.example.test/fail' => Http::response('error', 500),
    ]);

    $job = new DeliverWebhook(
        subscriptionId: $subscription->id,
        eventType: 'order.created',
        payload: ['id' => 99],
        eventId: 'evt-500',
        timestamp: 1_700_000_100,
    );

    $thrown = false;

    try {
        $job->handle(app(WebhookService::class));
    } catch (\RuntimeException) {
        $thrown = true;
    }

    expect($thrown)->toBeTrue();

    $this->assertDatabaseHas('webhook_deliveries', [
        'subscription_id' => $subscription->id,
        'event_id' => 'evt-500',
        'status' => 'failed',
        'response_code' => 500,
    ]);
});
