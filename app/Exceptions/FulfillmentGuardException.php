<?php

namespace App\Exceptions;

use RuntimeException;

class FulfillmentGuardException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Fulfillment cannot be created until payment is confirmed.');
    }
}
