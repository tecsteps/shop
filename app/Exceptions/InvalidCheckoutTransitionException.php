<?php

namespace App\Exceptions;

use App\Enums\CheckoutStatus;
use Exception;

class InvalidCheckoutTransitionException extends Exception
{
    public static function fromStatus(CheckoutStatus $from, string $action): self
    {
        return new self("Cannot {$action} a checkout in the \"{$from->value}\" state.");
    }
}
