<?php

namespace App\ValueObjects;

class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerRefundId = null,
    ) {}
}
