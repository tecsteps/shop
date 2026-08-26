<?php

use App\Services\WebhookService;

it('generates a valid HMAC-SHA256 signature', function () {
    $service = new WebhookService;
    $signature = $service->sign('{"event":"order.created"}', 'test-secret');

    expect($signature)->toBe(hash_hmac('sha256', '{"event":"order.created"}', 'test-secret'));
});

it('verifies a valid signature', function () {
    $service = new WebhookService;
    $signature = $service->sign('payload', 'secret');

    expect($service->verify('payload', $signature, 'secret'))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $service = new WebhookService;
    $signature = $service->sign('payload', 'secret');

    expect($service->verify('tampered', $signature, 'secret'))->toBeFalse();
});

it('rejects an incorrect secret', function () {
    $service = new WebhookService;
    $signature = $service->sign('payload', 'secret-a');

    expect($service->verify('payload', $signature, 'secret-b'))->toBeFalse();
});
