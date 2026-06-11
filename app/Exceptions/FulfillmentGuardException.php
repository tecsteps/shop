<?php

namespace App\Exceptions;

use App\Enums\FinancialStatus;
use Exception;

class FulfillmentGuardException extends Exception
{
    public static function forFinancialStatus(FinancialStatus $status): self
    {
        return new self(
            "Fulfillment cannot be created until payment is confirmed (financial status is \"{$status->value}\").",
        );
    }
}
