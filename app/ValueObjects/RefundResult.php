<?php

namespace App\ValueObjects;

use App\Enums\RefundStatus;

class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly RefundStatus $status,
        public readonly ?string $reference,
        public readonly ?string $errorCode = null,
        public readonly ?string $message = null,
        public readonly array $raw = [],
    ) {}

    public static function processed(string $reference): self
    {
        return new self(
            success: true,
            status: RefundStatus::Processed,
            reference: $reference,
            raw: ['provider' => 'mock', 'status' => RefundStatus::Processed->value, 'reference' => $reference],
        );
    }

    public static function failed(string $errorCode, string $message): self
    {
        return new self(
            success: false,
            status: RefundStatus::Failed,
            reference: null,
            errorCode: $errorCode,
            message: $message,
            raw: ['provider' => 'mock', 'status' => RefundStatus::Failed->value, 'error_code' => $errorCode],
        );
    }
}
