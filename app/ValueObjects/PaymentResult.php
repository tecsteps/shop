<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public PaymentStatus $status,
        public ?string $providerPaymentId = null,
        public ?string $errorCode = null,
        public array $raw = [],
    ) {}
}
