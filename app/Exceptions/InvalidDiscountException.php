<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a discount code fails validation.
 *
 * The `reason` property carries a stable machine code so callers can branch on
 * it: not_found, expired, not_yet_active, usage_limit_reached, minimum_not_met,
 * not_applicable.
 */
class InvalidDiscountException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : $reason);
    }
}
