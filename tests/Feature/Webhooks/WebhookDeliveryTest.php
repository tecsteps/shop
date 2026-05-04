<?php

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEventType;
use App\Enums\WebhookSubscriptionStatus;
use App\Jobs\DeliverWebhook;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ProductService;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function webhookActiveSubscription(Store $store, WebhookEventType $eventType): WebhookSubscription
{
    return WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => $eventType,
        'status' => WebhookSubscriptionStatus::Active,
    ]);
}

function expectQueuedWebhookDelivery(WebhookSubscription $subscription, WebhookEventType $eventType, callable $payloadMatches): void
{
    $delivery = $subscription->deliveries()->sole();

    expect($delivery)->toBeInstanceOf(WebhookDelivery::class)
        ->and($delivery->status)->toBe(WebhookDeliveryStatus::Pending)
        ->and($delivery->attempt_count)->toBe(1);

    Queue::assertPushed(DeliverWebhook::class, function (DeliverWebhook $job) use ($delivery, $eventType, $payloadMatches): bool {
        return $job->deliveryId === $delivery->getKey()
            && $job->eventType === $eventType->value
            && data_get($job->payload, 'id') === $delivery->event_id
            && data_get($job->payload, 'event_type') === $eventType->value
            && $payloadMatches($job->payload);
    });
    Queue::assertPushed(DeliverWebhook::class, 1);
}

function webhookCheckoutStore(): Store
{
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);

    TaxSettings::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 0, 'shipping_taxable' => false],
    ]);

    return $store;
}

function webhookCheckoutVariant(Store $store): ProductVariant
{
    $product = Product::factory()
        ->withDefaultVariant(2500)
        ->create(['store_id' => $store->getKey()]);

    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    $variant->forceFill([
        'requires_shipping' => false,
        'weight_g' => 0,
    ])->save();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

    return $variant->refresh();
}

test('dispatch creates delivery records and queues matching active subscriptions', function (): void {
    Queue::fake([DeliverWebhook::class]);

    $store = Store::factory()->create();
    $subscription = WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookEventType::OrderCreated,
        'status' => WebhookSubscriptionStatus::Active,
    ]);
    WebhookSubscription::factory()->create([
        'store_id' => $store->getKey(),
        'event_type' => WebhookEventType::ProductUpdated,
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    app(WebhookService::class)->dispatch($store, WebhookEventType::OrderCreated->value, [
        'order' => ['id' => 10],
    ]);

    Queue::assertPushed(DeliverWebhook::class, function (DeliverWebhook $job) use ($subscription): bool {
        return $job->eventType === WebhookEventType::OrderCreated->value
            && WebhookDelivery::query()->whereKey($job->deliveryId)->where('subscription_id', $subscription->getKey())->exists();
    });
    Queue::assertPushed(DeliverWebhook::class, 1);

    expect($subscription->deliveries()->count())->toBe(1);
});

test('product creation queues product created webhooks', function (): void {
    Queue::fake([DeliverWebhook::class]);

    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);
    $subscription = webhookActiveSubscription($store, WebhookEventType::ProductCreated);

    $product = app(ProductService::class)->create($store, [
        'title' => 'Webhook Draft Product',
        'price_amount' => 1500,
    ]);

    expectQueuedWebhookDelivery(
        $subscription,
        WebhookEventType::ProductCreated,
        fn (array $payload): bool => data_get($payload, 'data.product.id') === $product->getKey()
            && data_get($payload, 'data.product.title') === 'Webhook Draft Product',
    );
});

test('product updates queue product updated webhooks', function (): void {
    Queue::fake([DeliverWebhook::class]);

    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);
    $product = app(ProductService::class)->create($store, [
        'title' => 'Original Product',
        'price_amount' => 1500,
    ]);
    $subscription = webhookActiveSubscription($store, WebhookEventType::ProductUpdated);

    $updated = app(ProductService::class)->update($product, [
        'title' => 'Updated Product',
    ]);

    expectQueuedWebhookDelivery(
        $subscription,
        WebhookEventType::ProductUpdated,
        fn (array $payload): bool => data_get($payload, 'data.product.id') === $updated->getKey()
            && data_get($payload, 'data.product.title') === 'Updated Product',
    );
});

test('product archive queues product deleted webhooks', function (): void {
    Queue::fake([DeliverWebhook::class]);

    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);
    $product = app(ProductService::class)->create($store, [
        'title' => 'Archived Product',
        'price_amount' => 1500,
    ]);
    $subscription = webhookActiveSubscription($store, WebhookEventType::ProductDeleted);

    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Archived);

    expectQueuedWebhookDelivery(
        $subscription,
        WebhookEventType::ProductDeleted,
        fn (array $payload): bool => data_get($payload, 'data.product.id') === $product->getKey()
            && data_get($payload, 'data.product.status') === \App\Enums\ProductStatus::Archived->value,
    );
});

test('checkout completion queues checkout completed webhooks', function (): void {
    Queue::fake([DeliverWebhook::class]);

    $store = webhookCheckoutStore();
    $variant = webhookCheckoutVariant($store);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'Test',
            'last_name' => 'Buyer',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, null);
    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');
    $subscription = webhookActiveSubscription($store, WebhookEventType::CheckoutCompleted);

    $order = app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expectQueuedWebhookDelivery(
        $subscription,
        WebhookEventType::CheckoutCompleted,
        fn (array $payload): bool => data_get($payload, 'data.checkout.id') === $checkout->getKey()
            && data_get($payload, 'data.checkout.email') === 'buyer@example.test'
            && data_get($payload, 'data.checkout.total_amount') === 5000
            && data_get($payload, 'data.order.id') === $order->getKey(),
    );
});

test('deliver webhook posts signed json payload and records success', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/webhooks/orders' => Http::response(['ok' => true], 200),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'target_url' => 'https://example.com/webhooks/orders',
        'signing_secret_encrypted' => 'whsec_test_secret',
    ]);
    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Pending,
    ]);
    $payload = [
        'id' => $delivery->event_id,
        'api_version' => '2026-05',
        'event_type' => WebhookEventType::OrderCreated->value,
        'store_id' => $subscription->store_id,
        'data' => ['order' => ['id' => 10]],
    ];

    (new DeliverWebhook($delivery->getKey(), WebhookEventType::OrderCreated->value, $payload))
        ->handle(app(WebhookService::class));

    Http::assertSent(function ($request) use ($delivery, $subscription): bool {
        $timestamp = $request->header('X-Platform-Timestamp')[0] ?? '';
        $signature = $request->header('X-Platform-Signature')[0] ?? '';

        return $request->url() === 'https://example.com/webhooks/orders'
            && $request->header('X-Platform-Event')[0] === WebhookEventType::OrderCreated->value
            && $request->header('X-Platform-Delivery-Id')[0] === $delivery->event_id
            && app(WebhookService::class)->verify($timestamp.'.'.$request->body(), $signature, $subscription->fresh()->signing_secret_encrypted);
    });

    $delivery->refresh();

    expect($delivery->status)->toBe(WebhookDeliveryStatus::Success)
        ->and($delivery->response_code)->toBe(200)
        ->and($delivery->attempt_count)->toBe(1);
});

test('failed deliveries pause subscription after five consecutive failures', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://example.com/webhooks/failing' => Http::response('nope', 500),
    ]);

    $subscription = WebhookSubscription::factory()->create([
        'target_url' => 'https://example.com/webhooks/failing',
        'status' => WebhookSubscriptionStatus::Active,
    ]);

    WebhookDelivery::factory()->count(4)->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Failed,
        'last_attempt_at' => now()->subMinutes(5),
    ]);

    $delivery = WebhookDelivery::factory()->create([
        'subscription_id' => $subscription->getKey(),
        'status' => WebhookDeliveryStatus::Pending,
    ]);

    expect(fn () => (new DeliverWebhook($delivery->getKey(), WebhookEventType::OrderCreated->value, [
        'id' => $delivery->event_id,
        'data' => ['order' => ['id' => 10]],
    ]))->handle(app(WebhookService::class)))->toThrow(\RuntimeException::class);

    expect($subscription->refresh()->status)->toBe(WebhookSubscriptionStatus::Paused)
        ->and($delivery->refresh()->status)->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->response_code)->toBe(500);
});

test('delivery job uses the required retry schedule', function (): void {
    $job = new DeliverWebhook(1, WebhookEventType::OrderCreated->value, []);

    expect($job->tries)->toBe(6)
        ->and($job->backoff())->toBe([60, 300, 1800, 7200, 43200]);
});
