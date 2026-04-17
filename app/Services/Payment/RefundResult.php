<?php

namespace App\Services\Payment;

class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $providerRefundId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        /** @var array<string, mixed> */
        public readonly array $rawResponse = [],
    ) {}
}
