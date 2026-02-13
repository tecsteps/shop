<?php

namespace App\Exceptions;

use App\Enums\ProductStatus;
use RuntimeException;

class InvalidProductTransitionException extends RuntimeException
{
    public function __construct(
        public readonly ProductStatus $fromStatus,
        public readonly ProductStatus $toStatus,
        string $reason = '',
    ) {
        $message = "Cannot transition product from {$fromStatus->value} to {$toStatus->value}";

        if ($reason) {
            $message .= ": {$reason}";
        }

        parent::__construct($message);
    }
}
