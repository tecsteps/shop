<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $referenceId,
        public PaymentStatus $status,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
