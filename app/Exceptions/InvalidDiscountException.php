<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidDiscountException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message = '',
    ) {
        parent::__construct($message === '' ? $reason : $message);
    }

    public static function notFound(): self
    {
        return new self('discount_not_found', 'Discount code not found.');
    }

    public static function expired(): self
    {
        return new self('discount_expired', 'Discount has expired.');
    }

    public static function notYetActive(): self
    {
        return new self('discount_not_yet_active', 'Discount is not yet active.');
    }

    public static function usageLimitReached(): self
    {
        return new self('discount_usage_limit_reached', 'Discount usage limit has been reached.');
    }

    public static function minimumNotMet(): self
    {
        return new self('discount_min_purchase_not_met', 'Cart subtotal does not meet the minimum purchase amount.');
    }

    public static function disabled(): self
    {
        return new self('discount_disabled', 'Discount is disabled.');
    }
}
