<?php

namespace App\Services\Payments;

class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $providerRefundId,
        public readonly ?string $errorMessage = null,
    ) {}
}
