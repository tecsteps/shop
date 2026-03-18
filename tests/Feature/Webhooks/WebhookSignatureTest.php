<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->service = app(WebhookService::class);
});

it('signs a payload with HMAC-SHA256', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret-key';

    $signature = $this->service->sign($payload, $secret);

    expect($signature)->toBe(hash_hmac('sha256', $payload, $secret));
});

it('verifies a valid signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret-key';

    $signature = $this->service->sign($payload, $secret);

    expect($this->service->verify($payload, $signature, $secret))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret-key';

    expect($this->service->verify($payload, 'invalid-signature', $secret))->toBeFalse();
});

it('rejects a signature with wrong secret', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'correct-secret';
    $wrongSecret = 'wrong-secret';

    $signature = $this->service->sign($payload, $secret);

    expect($this->service->verify($payload, $signature, $wrongSecret))->toBeFalse();
});

it('rejects a signature with tampered payload', function () {
    $payload = '{"event":"order.created"}';
    $secret = 'test-secret-key';

    $signature = $this->service->sign($payload, $secret);
    $tamperedPayload = '{"event":"order.deleted"}';

    expect($this->service->verify($tamperedPayload, $signature, $secret))->toBeFalse();
});

it('produces different signatures for different payloads', function () {
    $secret = 'test-secret-key';

    $sig1 = $this->service->sign('payload-1', $secret);
    $sig2 = $this->service->sign('payload-2', $secret);

    expect($sig1)->not->toBe($sig2);
});

it('produces different signatures for different secrets', function () {
    $payload = '{"event":"order.created"}';

    $sig1 = $this->service->sign($payload, 'secret-1');
    $sig2 = $this->service->sign($payload, 'secret-2');

    expect($sig1)->not->toBe($sig2);
});
