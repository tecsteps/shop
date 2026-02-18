<?php

namespace App\Exceptions;

use App\Enums\FinancialStatus;
use RuntimeException;

class FulfillmentGuardException extends RuntimeException
{
    public function __construct(
        public readonly FinancialStatus $financialStatus,
    ) {
        parent::__construct(
            "Fulfillment cannot be created: order financial status is {$financialStatus->value}."
        );
    }
}
