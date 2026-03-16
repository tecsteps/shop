<?php

namespace App\ValueObjects;

use App\Models\Discount;

readonly class DiscountValidationResult
{
    public function __construct(
        public bool $valid,
        public ?Discount $discount = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null
    ) {}

    public static function success(Discount $discount): self
    {
        return new self(valid: true, discount: $discount);
    }

    public static function failure(string $errorCode, string $errorMessage): self
    {
        return new self(valid: false, errorCode: $errorCode, errorMessage: $errorMessage);
    }
}
