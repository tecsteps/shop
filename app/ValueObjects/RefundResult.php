<?php

namespace App\ValueObjects;

use App\Enums\RefundStatus;

final readonly class RefundResult
{
    public function __construct(
        public RefundStatus $status,
        public ?string $providerRefundId,
        public int $amount,
        public ?string $errorMessage = null,
    ) {}

    public function successful(): bool
    {
        return $this->status === RefundStatus::Processed;
    }
}
