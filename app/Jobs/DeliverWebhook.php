<?php

namespace App\Jobs;

use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    public int $timeout = 20;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public int $subscriptionId,
        public string $deliveryId,
        public string $eventType,
        public array $payload,
        public int $timestamp,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(WebhookService $webhooks): void
    {
        $webhooks->deliver($this->subscriptionId, $this->deliveryId, $this->eventType, $this->payload, $this->timestamp);
    }
}
