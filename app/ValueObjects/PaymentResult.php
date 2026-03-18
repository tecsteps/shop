<?php

namespace App\ValueObjects;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $referenceId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}
}
