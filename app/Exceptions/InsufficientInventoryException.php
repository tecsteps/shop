<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an inventory reservation would exceed available stock under a
 * `deny` oversell policy.
 */
class InsufficientInventoryException extends RuntimeException
{
    //
}
