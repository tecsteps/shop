<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\OauthClient;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

// --- WebhookService ---

it('signs a payload with HMAC-SHA256', function () {
    $service = app(WebhookService::class);

    $signature = $service->sign('test-payload', 'secret-key');

    expect($signature)->toBe(hash_hmac('sha256', 'test-payload', 'secret-key'));
});

it('verifies a valid signature', function () {
    $service = app(WebhookService::class);

    $signature = $service->sign('test-payload', 'secret-key');

    expect($service->verify('test-payload', $signature, 'secret-key'))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $service = app(WebhookService::class);

    expect($service->verify('test-payload', 'invalid-signature', 'secret-key'))->toBeFalse();
});

it('dispatches webhook to matching subscriptions', function () {
    Queue::fake();

    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'status' => 'active',
    ]);

    $service = app(WebhookService::class);
    $service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertPushed(DeliverWebhook::class);

    $this->assertDatabaseHas('webhook_deliveries', [
        'status' => 'pending',
    ]);
});

it('does not dispatch to paused subscriptions', function () {
    Queue::fake();

    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
        'status' => 'paused',
    ]);

    $service = app(WebhookService::class);
    $service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNothingPushed();
});

it('does not dispatch for non-matching event types', function () {
    Queue::fake();

    WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'product.created',
        'status' => 'active',
    ]);

    $service = app(WebhookService::class);
    $service->dispatch($this->store, 'order.created', ['order_id' => 1]);

    Queue::assertNothingPushed();
});

// --- DeliverWebhook Job ---

it('delivers a webhook successfully', function () {
    Http::fake([
        '*' => Http::response('OK', 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
        'event_type' => 'order.created',
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'event_id' => 'test-event-123',
        'attempt_count' => 0,
        'status' => 'pending',
    ]);

    $job = new DeliverWebhook($delivery->id, ['order_id' => 1]);
    $job->handle(app(WebhookService::class));

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(200)
        ->and($delivery->attempt_count)->toBe(1);
});

// --- Model Tests ---

it('creates an app with factory', function () {
    $app = App::factory()->create(['name' => 'Test App']);

    expect($app->name)->toBe('Test App')
        ->and($app->status->value)->toBe('active');
});

it('creates an app installation', function () {
    $app = App::factory()->create();

    $installation = AppInstallation::factory()->create([
        'store_id' => $this->store->id,
        'app_id' => $app->id,
    ]);

    expect($installation->app->id)->toBe($app->id)
        ->and($installation->scopes_json)->toBeArray();
});

it('creates an oauth client', function () {
    $app = App::factory()->create();

    $client = OauthClient::factory()->create([
        'app_id' => $app->id,
    ]);

    expect($client->app->id)->toBe($app->id)
        ->and($client->redirect_uris_json)->toBeArray();
});

it('creates a webhook subscription with factory', function () {
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $this->store->id,
    ]);

    expect($subscription->status)->toBe(WebhookSubscriptionStatus::Active)
        ->and($subscription->event_type)->not->toBeEmpty();
});
