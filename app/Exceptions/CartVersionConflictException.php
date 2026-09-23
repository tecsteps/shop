<?php

namespace App\Exceptions;

use RuntimeException;

class CartVersionConflictException extends RuntimeException
{
    public function __construct(public readonly int $currentVersion)
    {
        parent::__construct('The cart has been modified. Please refresh and try again.');
    }
}
