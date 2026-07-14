<?php

namespace App\ValueObjects;

final readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public string $providerRefundId,
        public string $status = 'processed',
    ) {}
}
