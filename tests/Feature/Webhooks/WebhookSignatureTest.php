<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->webhooks = app(WebhookService::class);
});

it('generates a valid HMAC-SHA256 signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->webhooks->sign($payload, $secret);

    $expected = WebhookService::SIGNATURE_PREFIX.hash_hmac('sha256', $payload, $secret);

    expect($signature)->toBe($expected);
});

it('verifies a valid signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->webhooks->sign($payload, $secret);

    expect($this->webhooks->verify($payload, $signature, $secret))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $secret = 'test-secret';
    $signature = $this->webhooks->sign('{"event":"order.created"}', $secret);

    expect($this->webhooks->verify('{"event":"order.updated"}', $signature, $secret))->toBeFalse();
});

it('rejects an incorrect secret', function () {
    $payload = '{"event":"order.created"}';

    $signature = $this->webhooks->sign($payload, 'secret-a');

    expect($this->webhooks->verify($payload, $signature, 'secret-b'))->toBeFalse();
});
