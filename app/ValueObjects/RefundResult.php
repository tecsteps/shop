<?php

namespace App\ValueObjects;

use App\Enums\RefundStatus;

final readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public RefundStatus $status,
        public ?string $providerRefundId = null,
        public ?string $errorCode = null,
    ) {}
}
