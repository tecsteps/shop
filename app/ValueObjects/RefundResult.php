<?php

namespace App\ValueObjects;

readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public string $providerRefundId,
        public string $status,
    ) {}
}
