<?php

use App\Services\WebhookService;

it('signs payloads with HMAC-SHA256', function (): void {
    $service = app(WebhookService::class);
    $sig = $service->sign('{"hello":"world"}', 'topsecret');

    expect($sig)->toMatch('/^[a-f0-9]{64}$/');
    expect($sig)->toBe(hash_hmac('sha256', '{"hello":"world"}', 'topsecret'));
});

it('verifies a matching signature', function (): void {
    $service = app(WebhookService::class);
    $sig = $service->sign('payload', 'secret');

    expect($service->verify('payload', $sig, 'secret'))->toBeTrue();
});

it('rejects a mismatched signature', function (): void {
    $service = app(WebhookService::class);
    $sig = $service->sign('payload', 'secret');

    expect($service->verify('payload', $sig, 'wrong_secret'))->toBeFalse();
    expect($service->verify('tampered', $sig, 'secret'))->toBeFalse();
});
