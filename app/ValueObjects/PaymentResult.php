<?php

namespace App\ValueObjects;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $referenceId,
        public string $status,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
