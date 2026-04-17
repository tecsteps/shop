<?php

namespace App\Services\Payments;

class PaymentResult
{
    public function __construct(
        public readonly bool $succeeded,
        public readonly string $providerPaymentId,
        public readonly string $status,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function success(string $providerId, string $status, array $raw = []): self
    {
        return new self(true, $providerId, $status, null, $raw);
    }

    public static function failure(string $error, string $providerId = '', array $raw = []): self
    {
        return new self(false, $providerId, 'failed', $error, $raw);
    }
}
