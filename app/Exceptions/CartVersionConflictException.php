<?php

namespace App\Exceptions;

use RuntimeException;

class CartVersionConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The cart changed in another session. Refresh and try again.', 409);
    }
}
