<?php

namespace App\Exceptions;

use App\Models\Cart;

class CartVersionMismatchException extends DomainException
{
    public function __construct(public readonly Cart $cart)
    {
        parent::__construct('The cart changed in another request. Refresh and try again.');
    }
}
