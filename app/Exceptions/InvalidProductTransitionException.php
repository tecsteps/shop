<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidProductTransitionException extends RuntimeException
{
    /**
     * Create an exception for a disallowed status transition.
     */
    public static function transition(string $from, string $to, string $reason = ''): self
    {
        $message = "Cannot transition product from '{$from}' to '{$to}'.";

        if ($reason !== '') {
            $message .= ' '.$reason;
        }

        return new self($message);
    }

    /**
     * Create an exception for a disallowed product deletion.
     */
    public static function deletion(string $reason): self
    {
        return new self("Cannot delete product. {$reason}");
    }
}
