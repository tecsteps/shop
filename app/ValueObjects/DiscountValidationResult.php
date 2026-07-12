<?php

namespace App\ValueObjects;

use App\Models\Discount;

final readonly class DiscountValidationResult
{
    public function __construct(
        public bool $valid,
        public ?Discount $discount = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
