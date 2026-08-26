<?php

namespace App\ValueObjects;

use App\Models\Discount;

class DiscountValidationResult
{
    public function __construct(
        public bool $valid,
        public ?Discount $discount = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function valid(Discount $discount): self
    {
        return new self(true, $discount);
    }

    public static function invalid(string $errorCode, string $errorMessage): self
    {
        return new self(false, null, $errorCode, $errorMessage);
    }
}
