<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class CartVersionMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $cartId,
        public readonly int $expectedVersion,
        public readonly int $currentVersion,
    ) {
        parent::__construct(sprintf(
            'Cart version mismatch for cart %d. Expected %d, current %d.',
            $cartId,
            $expectedVersion,
            $currentVersion,
        ));
    }
}
