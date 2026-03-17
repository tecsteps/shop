<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public PaymentStatus $status,
        public ?string $providerPaymentId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        /** @var array<string, mixed> */
        public array $rawResponse = [],
    ) {}

    public static function success(PaymentStatus $status, string $providerPaymentId, array $rawResponse = []): self
    {
        return new self(
            success: true,
            status: $status,
            providerPaymentId: $providerPaymentId,
            rawResponse: $rawResponse,
        );
    }

    public static function failure(string $errorCode, string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            success: false,
            status: PaymentStatus::Failed,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse,
        );
    }
}
