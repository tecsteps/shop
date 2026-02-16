<?php

use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(WebhookService::class);
});

it('delivers a webhook to a subscribed URL', function () {
    Queue::fake();

    WebhookSubscription::factory()->for($this->ctx['store'])->create([
        'event_types_json' => ['order.created'],
        'status' => 'active',
    ]);

    $this->service->dispatch($this->ctx['store'], 'order.created', ['order_id' => 1]);

    Queue::assertPushed(\App\Jobs\DeliverWebhook::class);
});

it('does not dispatch for unsubscribed events', function () {
    Queue::fake();

    WebhookSubscription::factory()->for($this->ctx['store'])->create([
        'event_types_json' => ['order.updated'],
        'status' => 'active',
    ]);

    $this->service->dispatch($this->ctx['store'], 'order.created', ['order_id' => 1]);

    Queue::assertNotPushed(\App\Jobs\DeliverWebhook::class);
});
