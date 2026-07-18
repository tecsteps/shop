<?php

namespace App\Exceptions;

use Exception;

class InvalidDiscountException extends Exception
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
