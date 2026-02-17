<?php

use App\Services\WebhookService;

test('sign generates hmac sha256 signature', function () {
    $service = new WebhookService;
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $service->sign($payload, $secret);

    expect($signature)->toBe(hash_hmac('sha256', $payload, $secret));
});

test('verify returns true for valid signature', function () {
    $service = new WebhookService;
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $service->sign($payload, $secret);

    expect($service->verify($payload, $signature, $secret))->toBeTrue();
});

test('verify returns false for invalid signature', function () {
    $service = new WebhookService;
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    expect($service->verify($payload, 'invalid-signature', $secret))->toBeFalse();
});

test('verify returns false for tampered payload', function () {
    $service = new WebhookService;
    $secret = 'test-secret';

    $signature = $service->sign('{"original":"payload"}', $secret);

    expect($service->verify('{"tampered":"payload"}', $signature, $secret))->toBeFalse();
});
