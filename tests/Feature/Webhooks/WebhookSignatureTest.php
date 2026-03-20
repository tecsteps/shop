<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->service = new WebhookService;
});

it('generates a valid HMAC-SHA256 signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->service->sign($payload, $secret);

    $expected = hash_hmac('sha256', $payload, $secret);
    expect($signature)->toBe($expected)
        ->and(strlen($signature))->toBe(64);
});

it('verifies a valid signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->service->sign($payload, $secret);
    $result = $this->service->verify($payload, $signature, $secret);

    expect($result)->toBeTrue();
});

it('rejects a tampered payload', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->service->sign($payload, $secret);
    $result = $this->service->verify('{"event":"order.TAMPERED"}', $signature, $secret);

    expect($result)->toBeFalse();
});

it('rejects an incorrect secret', function () {
    $payload = '{"event":"order.created"}';

    $signature = $this->service->sign($payload, 'secret-a');
    $result = $this->service->verify($payload, $signature, 'secret-b');

    expect($result)->toBeFalse();
});
