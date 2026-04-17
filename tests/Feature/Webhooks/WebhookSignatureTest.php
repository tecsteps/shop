<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->webhookService = new WebhookService;
});

it('generates a valid HMAC-SHA256 signature', function () {
    $payload = '{"order_id":123}';
    $secret = 'my-secret-key';

    $signature = $this->webhookService->sign($payload, $secret);

    $expected = hash_hmac('sha256', $payload, $secret);

    expect($signature)->toBe($expected);
});

it('verifies a valid signature', function () {
    $payload = '{"order_id":123}';
    $secret = 'my-secret-key';

    $signature = $this->webhookService->sign($payload, $secret);

    expect($this->webhookService->verify($payload, $signature, $secret))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $payload = '{"order_id":123}';
    $secret = 'my-secret-key';

    $signature = $this->webhookService->sign($payload, $secret);

    $tamperedPayload = '{"order_id":456}';

    expect($this->webhookService->verify($tamperedPayload, $signature, $secret))->toBeFalse();
});

it('rejects an incorrect secret', function () {
    $payload = '{"order_id":123}';
    $correctSecret = 'correct-secret';
    $wrongSecret = 'wrong-secret';

    $signature = $this->webhookService->sign($payload, $correctSecret);

    expect($this->webhookService->verify($payload, $signature, $wrongSecret))->toBeFalse();
});
