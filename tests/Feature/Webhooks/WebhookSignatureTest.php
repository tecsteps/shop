<?php

use App\Services\WebhookService;

beforeEach(function () {
    $this->webhooks = new WebhookService;
});

test('generates a valid HMAC-SHA256 signature', function () {
    $payload = '{"event":"order.created"}';
    $timestamp = 1739300000;

    $signature = $this->webhooks->sign($payload, 'test-secret', $timestamp);

    expect($signature)->toBe(hash_hmac('sha256', "{$timestamp}.{$payload}", 'test-secret'));
});

test('verifies a valid signature', function () {
    $payload = '{"event":"order.created"}';
    $timestamp = 1739300000;

    $signature = $this->webhooks->sign($payload, 'test-secret', $timestamp);

    expect($this->webhooks->verify($payload, $signature, 'test-secret', $timestamp))->toBeTrue();
});

test('rejects a tampered payload', function () {
    $timestamp = 1739300000;
    $signature = $this->webhooks->sign('{"event":"order.created"}', 'test-secret', $timestamp);

    expect($this->webhooks->verify('{"event":"order.updated"}', $signature, 'test-secret', $timestamp))->toBeFalse();
});

test('rejects an incorrect secret', function () {
    $payload = '{"event":"order.created"}';
    $timestamp = 1739300000;
    $signature = $this->webhooks->sign($payload, 'secret-a', $timestamp);

    expect($this->webhooks->verify($payload, $signature, 'secret-b', $timestamp))->toBeFalse();
});

test('rejects a signature computed with a different timestamp', function () {
    $payload = '{"event":"order.created"}';
    $signature = $this->webhooks->sign($payload, 'test-secret', 1739300000);

    expect($this->webhooks->verify($payload, $signature, 'test-secret', 1739300001))->toBeFalse();
});
