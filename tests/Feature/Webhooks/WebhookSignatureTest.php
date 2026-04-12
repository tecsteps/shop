<?php

use App\Services\WebhookService;

it('signs payloads with sha256= prefix', function (): void {
    $signature = app(WebhookService::class)->sign('{"hello":"world"}', 'secret');

    expect($signature)->toStartWith('sha256=')
        ->and(strlen($signature))->toBe(7 + 64);
});

it('verifies a valid signature', function (): void {
    $service = app(WebhookService::class);
    $payload = '{"a":1}';
    $signature = $service->sign($payload, 'topsecret');

    expect($service->verify($payload, $signature, 'topsecret'))->toBeTrue();
});

it('rejects an invalid signature', function (): void {
    $service = app(WebhookService::class);
    $payload = '{"a":1}';

    expect($service->verify($payload, 'sha256=bogus', 'topsecret'))->toBeFalse();
});
