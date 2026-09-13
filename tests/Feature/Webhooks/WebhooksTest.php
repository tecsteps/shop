<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Events\OrderPaid;
use App\Events\ProductStatusChanged;
use App\Jobs\DeliverWebhook;
use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('signs and verifies payloads with hmac', function () {
    $webhooks = app(WebhookService::class);

    $signature = $webhooks->sign('{"hello":"world"}', 'secret');

    expect($signature)->toBe(hash_hmac('sha256', '{"hello":"world"}', 'secret'))
        ->and($webhooks->verify('{"hello":"world"}', $signature, 'secret'))->toBeTrue()
        ->and($webhooks->verify('{"hello":"world"}', 'invalid', 'secret'))->toBeFalse();
});

it('creates subscriptions and dispatches deliveries to matching subscribers', function () {
    Queue::fake([DeliverWebhook::class]);
    ['store' => $store] = $this->createStoreContext();
    $webhooks = app(WebhookService::class);

    $subscription = $webhooks->subscribe($store, 'order.created', 'https://example.com/hooks');

    expect($subscription->status)->toBe(WebhookSubscriptionStatus::Active);

    $webhooks->dispatch($store, 'order.created', ['id' => 1]);

    expect(WebhookDelivery::query()->where('subscription_id', $subscription->getKey())->count())->toBe(1);

    Queue::assertPushed(DeliverWebhook::class);
});

it('skips paused subscriptions on dispatch', function () {
    Queue::fake([DeliverWebhook::class]);
    ['store' => $store] = $this->createStoreContext();
    $webhooks = app(WebhookService::class);

    $subscription = $webhooks->subscribe($store, 'order.created', 'https://example.com/hooks');
    $subscription->update(['status' => WebhookSubscriptionStatus::Paused]);

    $webhooks->dispatch($store, 'order.created', ['id' => 1]);

    expect(WebhookDelivery::query()->where('subscription_id', $subscription->getKey())->count())->toBe(0);

    Queue::assertNotPushed(DeliverWebhook::class);
});

it('delivers webhooks with signature headers and records success', function () {
    Http::fake(['*' => Http::response('ok', 200)]);
    ['store' => $store] = $this->createStoreContext();
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => 'order.created',
        'target_url' => 'https://example.com/hooks',
        'status' => WebhookSubscriptionStatus::Active,
    ]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Pending,
        'attempt_count' => 0,
    ]);

    (new DeliverWebhook($delivery->getKey(), 'order.created', ['id' => 1]))->handle(app(WebhookService::class));

    Http::assertSent(function ($request) use ($delivery) {
        return $request->url() === 'https://example.com/hooks'
            && $request->hasHeader('X-Platform-Signature')
            && $request->header('X-Platform-Event') === ['order.created']
            && $request->header('X-Platform-Delivery-Id') === [$delivery->event_id];
    });

    expect($delivery->refresh()->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(200)
        ->and($delivery->attempt_count)->toBe(1);
});

it('marks deliveries failed after exhausting retries', function () {
    Http::fake(['*' => Http::response('error', 500)]);
    ['store' => $store] = $this->createStoreContext();
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'status' => WebhookSubscriptionStatus::Active,
    ]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Pending,
        'attempt_count' => 5,
    ]);

    $job = new DeliverWebhook($delivery->getKey(), 'order.created', ['id' => 1]);

    expect($job->tries)->toBe(6)
        ->and($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);

    $job->handle(app(WebhookService::class));

    expect($delivery->refresh()->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->attempt_count)->toBe(6);
});

it('pauses subscriptions after five consecutive failures', function () {
    ['store' => $store] = $this->createStoreContext();
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    WebhookDelivery::factory()->count(5)->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Failed,
    ]);

    app(WebhookService::class)->recordFailure($subscription->refresh());

    expect($subscription->refresh()->status)->toBe(WebhookSubscriptionStatus::Paused);
});

it('dispatches webhooks when domain events fire', function () {
    Queue::fake([DeliverWebhook::class]);
    ['store' => $store] = $this->createStoreContext();
    $webhooks = app(WebhookService::class);

    $subscription = $webhooks->subscribe($store, 'order.paid', 'https://example.com/hooks');
    $order = Order::factory()->create([
        'store_id' => $store->getKey(),
        'payment_method' => PaymentMethod::CreditCard,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'placed_at' => now(),
    ]);

    event(new OrderPaid($order));

    expect(WebhookDelivery::query()->where('subscription_id', $subscription->getKey())->count())->toBe(1);
});

it('maps product status changes to created and deleted events', function () {
    Queue::fake([DeliverWebhook::class]);
    ['store' => $store] = $this->createStoreContext();
    $webhooks = app(WebhookService::class);

    $created = $webhooks->subscribe($store, 'product.created', 'https://example.com/hooks');
    $deleted = $webhooks->subscribe($store, 'product.deleted', 'https://example.com/hooks');
    $product = Product::factory()->create(['store_id' => $store->getKey()]);

    event(new ProductStatusChanged($product, ProductStatus::Draft, ProductStatus::Active));
    event(new ProductStatusChanged($product, ProductStatus::Active, ProductStatus::Archived));

    expect(WebhookDelivery::query()->where('subscription_id', $created->getKey())->count())->toBe(1)
        ->and(WebhookDelivery::query()->where('subscription_id', $deleted->getKey())->count())->toBe(1);
});
