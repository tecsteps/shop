<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

final class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly PaymentStatus $status,
        public readonly ?string $providerPaymentId = null,
        public readonly ?string $errorCode = null,
        public readonly array $raw = [],
    ) {}

    public static function captured(string $providerId, array $raw = []): self
    {
        return new self(true, PaymentStatus::Captured, $providerId, null, $raw);
    }

    public static function pending(string $providerId, array $raw = []): self
    {
        return new self(true, PaymentStatus::Pending, $providerId, null, $raw);
    }

    public static function declined(string $errorCode, array $raw = []): self
    {
        return new self(false, PaymentStatus::Failed, null, $errorCode, $raw);
    }
}
