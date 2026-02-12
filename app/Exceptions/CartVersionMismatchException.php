<?php

namespace App\Exceptions;

use App\Models\Cart;
use RuntimeException;

class CartVersionMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $expectedVersion,
        public readonly int $actualVersion,
        public readonly ?Cart $cart = null,
    ) {
        parent::__construct('Cart version conflict.');
    }
}
