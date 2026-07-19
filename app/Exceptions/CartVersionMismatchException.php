<?php

namespace App\Exceptions;

use App\Models\Cart;
use RuntimeException;

/**
 * Thrown when a client-submitted expected cart version does not match the
 * current version (optimistic concurrency, spec 05 §4.3). Carries the
 * current cart so the API can return it in the 409 response body.
 */
class CartVersionMismatchException extends RuntimeException
{
    public function __construct(public readonly Cart $cart)
    {
        parent::__construct("Cart version mismatch: current version is {$cart->cart_version}.");
    }
}
