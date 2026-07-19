<?php

namespace App\ValueObjects;

/**
 * Result of a charge attempt at the payment provider (spec 05 §22).
 * Status is one of: pending, captured, failed.
 */
readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $referenceId,
        public string $status,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    /**
     * Successful charge with instant capture (credit card, PayPal).
     */
    public static function captured(string $referenceId): self
    {
        return new self(success: true, referenceId: $referenceId, status: 'captured');
    }

    /**
     * Successful initiation with deferred capture (bank transfer).
     */
    public static function pending(string $referenceId): self
    {
        return new self(success: true, referenceId: $referenceId, status: 'pending');
    }

    /**
     * Failed charge (decline, insufficient funds).
     */
    public static function failed(string $errorCode, string $errorMessage): self
    {
        return new self(
            success: false,
            referenceId: '',
            status: 'failed',
            errorCode: $errorCode,
            errorMessage: $errorMessage,
        );
    }
}
