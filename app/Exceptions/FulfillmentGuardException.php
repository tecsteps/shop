<?php

namespace App\Exceptions;

use RuntimeException;

class FulfillmentGuardException extends RuntimeException
{
    public function __construct(string $financialStatus)
    {
        parent::__construct("Fulfillment cannot be created: order financial status is '{$financialStatus}'. Payment must be confirmed first.");
    }
}
