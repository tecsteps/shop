<?php

namespace App\ValueObjects;

class RefundResult
{
    public function __construct(
        public bool $success,
        public ?string $providerRefundId,
        public string $status,
    ) {}
}
