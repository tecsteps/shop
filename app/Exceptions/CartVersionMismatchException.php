<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an optimistic-concurrency cart mutation is attempted with a stale
 * `expected_version` that no longer matches the cart's current `cart_version`.
 *
 * API callers translate this to HTTP 409 Conflict and return the current cart
 * state.
 */
class CartVersionMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $expectedVersion,
        public readonly int $currentVersion,
    ) {
        parent::__construct(
            "Cart version mismatch: expected {$expectedVersion}, current {$currentVersion}.",
        );
    }
}
