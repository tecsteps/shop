<?php

namespace App\ValueObjects;

/**
 * Result of a refund attempt at the payment provider (spec 05 §22).
 */
readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public string $providerRefundId,
        public string $status,
    ) {}
}
