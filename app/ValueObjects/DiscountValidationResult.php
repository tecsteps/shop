<?php

namespace App\ValueObjects;

use App\Models\Discount;

/**
 * Outcome of discount code validation (spec 05 §7.4).
 */
readonly class DiscountValidationResult
{
    public function __construct(
        public bool $valid,
        public ?Discount $discount,
        public ?string $errorCode,
        public ?string $errorMessage,
    ) {}

    public static function valid(Discount $discount): self
    {
        return new self(valid: true, discount: $discount, errorCode: null, errorMessage: null);
    }

    public static function invalid(string $errorCode, string $errorMessage): self
    {
        return new self(valid: false, discount: null, errorCode: $errorCode, errorMessage: $errorMessage);
    }
}
