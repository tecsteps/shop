<?php

use App\Services\WebhookService;

it('generates a valid HMAC-SHA256 signature', function () {
    $service = new WebhookService;
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $service->sign($payload, $secret);

    expect($signature)->toBe(hash_hmac('sha256', $payload, $secret));
});

it('verifies a valid signature', function () {
    $service = new WebhookService;
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $service->sign($payload, $secret);

    expect($service->verify($payload, $signature, $secret))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $service = new WebhookService;
    $secret = 'test-secret';

    $signature = $service->sign('{"event":"order.created"}', $secret);

    expect($service->verify('{"event":"order.updated"}', $signature, $secret))->toBeFalse();
});

it('rejects an incorrect secret', function () {
    $service = new WebhookService;
    $payload = '{"event":"order.created"}';

    $signature = $service->sign($payload, 'secret-a');

    expect($service->verify($payload, $signature, 'secret-b'))->toBeFalse();
});
