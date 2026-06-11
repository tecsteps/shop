<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->service = app(WebhookService::class);
});

it('generates a valid HMAC-SHA256 signature', function () {
    $payload = '{"event":"order.created"}';

    $signature = $this->service->sign($payload, 'test-secret');

    expect($signature)->toBe(hash_hmac('sha256', $payload, 'test-secret'));
});

it('verifies a valid signature', function () {
    $payload = '{"event":"order.created","data":{"id":42}}';

    $signature = $this->service->sign($payload, 'test-secret');

    expect($this->service->verify($payload, $signature, 'test-secret'))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $signature = $this->service->sign('{"event":"order.created","total":1000}', 'test-secret');

    $tampered = '{"event":"order.created","total":9999}';

    expect($this->service->verify($tampered, $signature, 'test-secret'))->toBeFalse();
});

it('rejects an incorrect secret', function () {
    $payload = '{"event":"order.created"}';

    $signature = $this->service->sign($payload, 'secret-a');

    expect($this->service->verify($payload, $signature, 'secret-b'))->toBeFalse();
});
