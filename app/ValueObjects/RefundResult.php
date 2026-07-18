<?php

namespace App\ValueObjects;

use App\Enums\RefundStatus;

readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public string $providerRefundId,
        public RefundStatus $status,
        public ?string $errorCode = null,
    ) {}
}
