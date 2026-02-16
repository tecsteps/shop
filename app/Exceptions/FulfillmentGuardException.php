<?php

namespace App\Exceptions;

use RuntimeException;

class FulfillmentGuardException extends RuntimeException
{
    public function __construct(string $message = 'Order is not eligible for fulfillment')
    {
        parent::__construct($message);
    }
}
