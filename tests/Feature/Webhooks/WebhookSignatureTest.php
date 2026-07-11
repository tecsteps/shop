<?php

use App\Services\WebhookService;

test('webhook signatures authenticate the timestamp and payload', function () {
    $webhooks = app(WebhookService::class);
    $payload = '{"order_id":42}';
    $timestamp = 1783764000;
    $signature = $webhooks->sign($payload, 'signing-secret', $timestamp);

    expect($signature)->toBe(hash_hmac('sha256', $timestamp.'.'.$payload, 'signing-secret'))
        ->and($webhooks->verify($payload, $signature, 'signing-secret', $timestamp))->toBeTrue()
        ->and($webhooks->verify('{"order_id":43}', $signature, 'signing-secret', $timestamp))->toBeFalse()
        ->and($webhooks->verify($payload, $signature, 'wrong-secret', $timestamp))->toBeFalse();
});
