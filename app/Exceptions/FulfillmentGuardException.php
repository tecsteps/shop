<?php

namespace App\Exceptions;

use RuntimeException;

class FulfillmentGuardException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An order must be paid before it can be fulfilled.', 422);
    }
}
