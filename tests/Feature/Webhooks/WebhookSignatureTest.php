<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->webhookService = new WebhookService;
});

it('generates a valid HMAC-SHA256 signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->webhookService->sign($payload, $secret);
    $expected = hash_hmac('sha256', $payload, $secret);

    expect($signature)->toBe($expected);
});

it('verifies a valid signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->webhookService->sign($payload, $secret);

    expect($this->webhookService->verify($payload, $signature, $secret))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret';

    $signature = $this->webhookService->sign($payload, $secret);
    $tamperedPayload = '{"event":"order.deleted"}';

    expect($this->webhookService->verify($tamperedPayload, $signature, $secret))->toBeFalse();
});

it('rejects an incorrect secret', function () {
    $payload = '{"event":"order.created"}';
    $secretA = 'secret-a';
    $secretB = 'secret-b';

    $signature = $this->webhookService->sign($payload, $secretA);

    expect($this->webhookService->verify($payload, $signature, $secretB))->toBeFalse();
});
