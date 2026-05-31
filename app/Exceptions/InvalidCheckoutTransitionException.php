<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a checkout state-machine transition is attempted from a state
 * that does not permit it (for example completing a checkout that has not yet
 * selected a payment method).
 */
class InvalidCheckoutTransitionException extends RuntimeException
{
    //
}
