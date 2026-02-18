<?php

namespace App\Exceptions;

use App\Enums\CheckoutStatus;
use RuntimeException;

class InvalidCheckoutTransitionException extends RuntimeException
{
    public function __construct(
        public readonly CheckoutStatus $from,
        public readonly CheckoutStatus $to,
    ) {
        parent::__construct(
            "Invalid checkout transition from {$from->value} to {$to->value}."
        );
    }
}
