<?php

namespace App\ValueObjects;

readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public ?string $providerRefundId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(string $providerRefundId): self
    {
        return new self(
            success: true,
            providerRefundId: $providerRefundId,
        );
    }

    public static function failure(string $errorCode, string $errorMessage): self
    {
        return new self(
            success: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }
}
