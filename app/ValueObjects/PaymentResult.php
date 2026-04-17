<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    public function __construct(
        public PaymentStatus $status,
        public ?string $providerPaymentId,
        public int $amount,
        public string $currency,
        public ?string $errorMessage = null,
    ) {}

    public function successful(): bool
    {
        return $this->status === PaymentStatus::Captured;
    }

    public function pending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function failed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }
}
