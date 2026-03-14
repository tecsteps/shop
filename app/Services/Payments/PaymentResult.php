<?php

namespace App\Services\Payments;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly string $providerPaymentId,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}
}
