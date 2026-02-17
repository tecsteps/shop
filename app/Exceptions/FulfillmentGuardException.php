<?php

namespace App\Exceptions;

use RuntimeException;

class FulfillmentGuardException extends RuntimeException
{
    public function __construct(string $message = 'Fulfillment cannot be created until payment is confirmed')
    {
        parent::__construct($message);
    }
}
