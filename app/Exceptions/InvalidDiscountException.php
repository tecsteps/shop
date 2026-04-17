<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidDiscountException extends RuntimeException
{
    public const CODE_NOT_FOUND = 'discount_not_found';

    public const CODE_EXPIRED = 'discount_expired';

    public const CODE_NOT_YET_ACTIVE = 'discount_not_yet_active';

    public const CODE_USAGE_LIMIT_REACHED = 'discount_usage_limit_reached';

    public const CODE_MIN_PURCHASE_NOT_MET = 'discount_min_purchase_not_met';

    public const CODE_NOT_APPLICABLE = 'discount_not_applicable';

    public function __construct(public readonly string $reason, ?string $message = null)
    {
        parent::__construct($message ?? $reason);
    }
}
