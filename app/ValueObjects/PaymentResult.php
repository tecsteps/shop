<?php

namespace App\ValueObjects;

final readonly class PaymentResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public bool $success,
        public string $referenceId,
        public string $status,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}
}
