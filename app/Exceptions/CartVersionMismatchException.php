<?php

namespace App\Exceptions;

use DomainException;

class CartVersionMismatchException extends DomainException
{
    public function __construct(public int $currentVersion)
    {
        parent::__construct('The cart has been modified. Please refresh and try again.');
    }
}
