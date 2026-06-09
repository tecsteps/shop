<?php

namespace App\Exceptions;

use Exception;

class InvalidDiscountException extends Exception
{
    public function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self('not_found', 'This discount code does not exist.');
    }

    public static function expired(): self
    {
        return new self('expired', 'This discount code has expired.');
    }

    public static function notYetActive(): self
    {
        return new self('not_yet_active', 'This discount code is not active yet.');
    }

    public static function usageLimitReached(): self
    {
        return new self('usage_limit_reached', 'This discount code has reached its usage limit.');
    }

    public static function minimumNotMet(int $minimumAmount): self
    {
        return new self('minimum_not_met', "The cart does not meet the minimum purchase amount of {$minimumAmount}.");
    }

    public static function disabled(): self
    {
        return new self('disabled', 'This discount code is not available.');
    }

    public static function notApplicable(): self
    {
        return new self('not_applicable', 'This discount code does not apply to any items in the cart.');
    }
}
