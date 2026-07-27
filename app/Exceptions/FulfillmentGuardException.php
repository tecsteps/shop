<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a fulfillment is attempted on an order whose financial status
 * does not allow it (spec 05 §11.5 fulfillment guard). Fulfillment requires
 * financial_status paid or partially_refunded.
 */
class FulfillmentGuardException extends RuntimeException
{
    public static function forFinancialStatus(string $financialStatus): self
    {
        return new self("Fulfillment cannot be created until payment is confirmed (financial status: {$financialStatus}).");
    }
}
