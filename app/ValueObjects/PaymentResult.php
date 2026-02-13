<?php

namespace App\ValueObjects;

/**
 * @phpstan-type PaymentResultStatus 'captured'|'pending'|'failed'
 */
readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $errorCode,
        public string $providerPaymentId,
        public ?array $rawResponse = null,
    ) {}
}
