<?php

namespace App\Exceptions;

use App\Models\Cart;
use RuntimeException;

class CartVersionConflictException extends RuntimeException
{
    public function __construct(public Cart $cart)
    {
        parent::__construct('The cart was modified by another request.');
    }
}
