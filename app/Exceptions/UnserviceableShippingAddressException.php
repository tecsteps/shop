<?php

namespace App\Exceptions;

use RuntimeException;

class UnserviceableShippingAddressException extends RuntimeException
{
    public static function forAddress(): self
    {
        return new self('Cannot ship to this address.');
    }
}
