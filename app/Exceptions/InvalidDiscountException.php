<?php

namespace App\Exceptions;

use Exception;

class InvalidDiscountException extends Exception
{
    public function __construct(public readonly string $reason, ?string $message = null)
    {
        parent::__construct($message ?? "Invalid discount: {$reason}");
    }
}
