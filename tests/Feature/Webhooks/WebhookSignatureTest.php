<?php

use App\Services\WebhookService;

test('webhook signatures are hmac sha256 digests', function (): void {
    $webhooks = app(WebhookService::class);
    $payload = '1714780800.{"id":"evt_1","data":{"order":{"id":1}}}';
    $secret = 'whsec_test_secret';
    $signature = $webhooks->sign($payload, $secret);

    expect($signature)->toBe(hash_hmac('sha256', $payload, $secret))
        ->and($webhooks->verify($payload, $signature, $secret))->toBeTrue();
});

test('webhook verification rejects tampered payloads and signatures', function (): void {
    $webhooks = app(WebhookService::class);
    $payload = '1714780800.{"id":"evt_1"}';
    $secret = 'whsec_test_secret';
    $signature = $webhooks->sign($payload, $secret);

    expect($webhooks->verify($payload.'.tampered', $signature, $secret))->toBeFalse()
        ->and($webhooks->verify($payload, str_repeat('0', 64), $secret))->toBeFalse();
});
