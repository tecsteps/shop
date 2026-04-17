<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidDiscountException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $code): self
    {
        return new self(
            reasonCode: 'discount_not_found',
            message: sprintf('Discount code "%s" was not found.', $code),
        );
    }

    public static function expired(string $code): self
    {
        return new self(
            reasonCode: 'discount_expired',
            message: sprintf('Discount code "%s" has expired.', $code),
        );
    }

    public static function notYetActive(string $code): self
    {
        return new self(
            reasonCode: 'discount_not_yet_active',
            message: sprintf('Discount code "%s" is not active yet.', $code),
        );
    }

    public static function usageLimitReached(string $code): self
    {
        return new self(
            reasonCode: 'discount_usage_limit_reached',
            message: sprintf('Discount code "%s" has reached its usage limit.', $code),
        );
    }

    public static function minimumNotMet(string $code, int $minimum): self
    {
        return new self(
            reasonCode: 'discount_min_purchase_not_met',
            message: sprintf('Discount code "%s" requires a minimum subtotal of %d.', $code, $minimum),
        );
    }

    public static function notApplicable(string $code): self
    {
        return new self(
            reasonCode: 'discount_not_applicable',
            message: sprintf('Discount code "%s" is not applicable to this cart.', $code),
        );
    }
}
