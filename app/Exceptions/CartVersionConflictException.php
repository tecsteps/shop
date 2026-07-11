<?php

namespace App\Exceptions;

use RuntimeException;

class CartVersionConflictException extends RuntimeException
{
    public function __construct(public readonly int $currentVersion)
    {
        parent::__construct('The cart was modified by another request.');
    }
}
