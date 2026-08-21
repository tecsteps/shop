<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

readonly class PaymentResult
{
    public function __construct(public PaymentStatus $status, public string $reference, public string $message = '', public ?string $errorCode = null) {}

    public function isSuccessful(): bool
    {
        return in_array($this->status, [PaymentStatus::Captured, PaymentStatus::Pending], true);
    }
}
