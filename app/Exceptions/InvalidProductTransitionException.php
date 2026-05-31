<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a product status transition violates the lifecycle state machine
 * (for example publishing without a priced variant, or reverting to draft while
 * order lines reference the product).
 */
class InvalidProductTransitionException extends RuntimeException
{
    //
}
