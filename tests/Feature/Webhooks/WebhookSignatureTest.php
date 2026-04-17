<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->service = new WebhookService;
});

it('generates HMAC-SHA256 signature', function () {
    $payload = '{"order_id":1}';
    $secret = 'my-secret-key';

    $signature = $this->service->sign($payload, $secret);

    expect($signature)->toBe(hash_hmac('sha256', $payload, $secret));
    expect(strlen($signature))->toBe(64);
});

it('verifies a valid signature', function () {
    $payload = '{"order_id":42}';
    $secret = 'test-secret';

    $signature = $this->service->sign($payload, $secret);
    $result = $this->service->verify($payload, $signature, $secret);

    expect($result)->toBeTrue();
});

it('rejects a tampered payload', function () {
    $payload = '{"order_id":42}';
    $secret = 'test-secret';

    $signature = $this->service->sign($payload, $secret);
    $result = $this->service->verify('{"order_id":99}', $signature, $secret);

    expect($result)->toBeFalse();
});

it('rejects a wrong secret', function () {
    $payload = '{"order_id":42}';

    $signature = $this->service->sign($payload, 'correct-secret');
    $result = $this->service->verify($payload, $signature, 'wrong-secret');

    expect($result)->toBeFalse();
});
