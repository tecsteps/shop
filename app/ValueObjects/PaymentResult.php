<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw  Mock provider response payload
     */
    public function __construct(
        public bool $success,
        public PaymentStatus $status,
        public ?string $providerPaymentId = null,
        public ?string $errorCode = null,
        public array $raw = [],
    ) {}

    public static function captured(string $providerPaymentId, array $raw = []): self
    {
        return new self(true, PaymentStatus::Captured, $providerPaymentId, null, $raw);
    }

    public static function pending(string $providerPaymentId, array $raw = []): self
    {
        return new self(true, PaymentStatus::Pending, $providerPaymentId, null, $raw);
    }

    public static function failed(string $errorCode, array $raw = []): self
    {
        return new self(false, PaymentStatus::Failed, null, $errorCode, $raw);
    }
}
