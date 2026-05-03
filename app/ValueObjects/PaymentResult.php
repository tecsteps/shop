<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly PaymentStatus $status,
        public readonly ?string $reference,
        public readonly ?string $errorCode,
        public readonly ?string $message,
        public readonly array $raw = [],
    ) {}

    public static function captured(string $reference): self
    {
        return new self(
            success: true,
            status: PaymentStatus::Captured,
            reference: $reference,
            errorCode: null,
            message: null,
            raw: ['provider' => 'mock', 'status' => PaymentStatus::Captured->value, 'reference' => $reference],
        );
    }

    public static function pending(string $reference): self
    {
        return new self(
            success: true,
            status: PaymentStatus::Pending,
            reference: $reference,
            errorCode: null,
            message: null,
            raw: ['provider' => 'mock', 'status' => PaymentStatus::Pending->value, 'reference' => $reference],
        );
    }

    public static function failed(string $errorCode, string $message): self
    {
        return new self(
            success: false,
            status: PaymentStatus::Failed,
            reference: null,
            errorCode: $errorCode,
            message: $message,
            raw: ['provider' => 'mock', 'status' => PaymentStatus::Failed->value, 'error_code' => $errorCode],
        );
    }
}
